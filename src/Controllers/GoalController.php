<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/functions.php';

use App\Models\Goal;
use App\Models\Category;
use App\Models\PurchasePlan;
use App\Services\PurchaseStrategy;
use DateTimeImmutable;
use Throwable;

final class GoalController
{
  private const CSRF_SESSION_KEY = 'goals_csrf';
  private const FORM_OLD_INPUT_KEY = 'goals_old_input';
  private const FORM_ERROR_KEY = 'goals_form_error';
  private const FORM_NOTICE_KEY = 'goals_form_notice';
  private const EDIT_OLD_INPUT_KEY = 'goals_edit_old_input';
  private const EDIT_ERROR_KEY = 'goals_edit_error';
  private const CONTRIBUTE_OLD_INPUT_KEY = 'goals_contribute_old_input';
  private const CONTRIBUTION_ERROR_KEY = 'goals_contribution_error';
  private const MAX_TITLE_LENGTH = 150;
  private const DEFAULT_PERIOD = 'month';
  private const PERIOD_WEEK = 'week';
  private const PERIOD_MONTH = 'month';
  private const PERIOD_YEAR = 'year';
  private const STATUS_CANCELLED = 'cancelled';
  private const DEADLINE_EXPIRED_TEXT = 'Срок истёк';
  private const PURCHASE_PLAN_OLD_INPUT_KEY = 'purchase_plan_old_input';

  public function index(): void
  {
    \requireAuth();
    $profile = \getAuthenticatedUserProfile();
    if ($profile === null) {
      \redirect('/login');
    }

    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $selectedPeriod = $this->normalizePeriod((string) (\query('period', self::DEFAULT_PERIOD) ?? self::DEFAULT_PERIOD));
    $goals = [];
    $error = \getFlash(self::FORM_ERROR_KEY);
    $notice = \getFlash(self::FORM_NOTICE_KEY);
    $oldInput = $this->takeOldInput();
    $editError = \getFlash(self::EDIT_ERROR_KEY);
    $editOldInput = $this->takeSessionArray(self::EDIT_OLD_INPUT_KEY);
    $contributionError = \getFlash(self::CONTRIBUTION_ERROR_KEY);
    $contributeOldInput = $this->takeSessionArray(self::CONTRIBUTE_OLD_INPUT_KEY);
    $csrf = $this->ensureCsrfToken();

    try {
      $goalModel = new Goal();
      $goals = $goalModel->getAllForUser(\getPdo(), $userId);
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'goals_index', 'user_id' => $userId]);
      $error = 'Не удалось загрузить список целей. Попробуйте позже.';
      $goals = [];
    }

    $preparedGoals = array_map(
      fn(array $goal): array => $this->prepareGoalForView($goal),
      $goals
    );

    $purchasePlanError = \getFlash('purchase_plan_form_error');
    $purchasePlanOldInput = $this->takePurchasePlanOldInput();
    $purchasePlansPrepared = [];
    try {
      $purchasePlanModel = new PurchasePlan();
      $rawPurchasePlans = $purchasePlanModel->getAllForUser(\getPdo(), $userId);
      $purchasePlansPrepared = $this->preparePurchasePlansForView($rawPurchasePlans);
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'purchase_plans_index', 'user_id' => $userId]);
      if ($purchasePlanError === null || $purchasePlanError === '') {
        $purchasePlanError = 'Не удалось загрузить планы покупок.';
      }
    }

    header('Content-Type: text/html; charset=utf-8');
    \render('savings', [
      'userName' => $profile['name'],
      'userEmail' => $profile['email'],
      'currentPath' => '/savings',
      'selectedPeriod' => $selectedPeriod,
      'goals' => $preparedGoals,
      'csrf' => $csrf,
      'formError' => $error,
      'formNotice' => $notice,
      'oldInput' => $oldInput,
      'editError' => $editError,
      'editOldInput' => $editOldInput,
      'contributionError' => $contributionError,
      'contributeOldInput' => $contributeOldInput,
      'purchasePlanError' => $purchasePlanError,
      'purchasePlanOldInput' => $purchasePlanOldInput,
      'purchasePlans' => $purchasePlansPrepared,
      'availableTerms' => PurchaseStrategy::AVAILABLE_TERMS,
    ]);
  }

  public function create(): void
  {
    \requireAuth();
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $selectedPeriod = $this->normalizePeriod((string) (\query('period', self::DEFAULT_PERIOD) ?? self::DEFAULT_PERIOD));
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash(self::FORM_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    $title = (string) \input('title', '');
    $targetAmount = (string) \input('target_amount', '');
    $period = (string) \input('period', '');
    $deadline = (string) \input('deadline', '');

    $oldInput = [
      'title' => $title,
      'target_amount' => $targetAmount,
      'period' => $period,
      'deadline' => $deadline,
    ];

    $validationError = $this->validateGoalInput($title, $targetAmount, $period, $deadline);
    if ($validationError !== null) {
      $this->storeOldInput($oldInput);
      \setFlash(self::FORM_ERROR_KEY, $validationError);
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    $goalData = [
      'user_id' => $userId,
      'title' => trim($title),
      'target_amount' => number_format((float) $targetAmount, 2, '.', ''),
      'period' => $period,
      'deadline' => $deadline,
    ];

    try {
      $goalModel = new Goal();
      $goalId = $goalModel->create(\getPdo(), $goalData);
      if ($goalId <= 0) {
        throw new \RuntimeException('Goal was not created.');
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'goals_create', 'user_id' => $userId]);
      $this->storeOldInput($oldInput);
      \setFlash(self::FORM_ERROR_KEY, 'Не удалось создать цель. Попробуйте позже.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::FORM_NOTICE_KEY, 'Цель успешно создана.');
    \redirect('/savings?period=' . rawurlencode($selectedPeriod));
  }

  public function update(): void
  {
    \requireAuth();
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $selectedPeriod = $this->normalizePeriod((string) (\query('period', self::DEFAULT_PERIOD) ?? self::DEFAULT_PERIOD));
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash(self::EDIT_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    $goalId = $this->normalizePositiveInt((string) \input('goal_id', ''));
    if ($goalId === null) {
      $this->abortNotFound();
    }

    $title = (string) \input('title', '');
    $targetAmount = (string) \input('target_amount', '');
    $period = (string) \input('period', '');
    $deadline = (string) \input('deadline', '');
    $oldInput = [
      'goal_id' => (string) $goalId,
      'title' => $title,
      'target_amount' => $targetAmount,
      'period' => $period,
      'deadline' => $deadline,
    ];

    $error = $this->validateGoalInput($title, $targetAmount, $period, $deadline);
    if ($error !== null) {
      $this->storeSessionArray(self::EDIT_OLD_INPUT_KEY, $oldInput);
      \setFlash(self::EDIT_ERROR_KEY, $error);
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    $goalData = [
      'title' => trim($title),
      'target_amount' => number_format((float) $targetAmount, 2, '.', ''),
      'period' => $period,
      'deadline' => $deadline,
    ];

    try {
      $model = new Goal();
      $goal = $model->findForUser(\getPdo(), $goalId, $userId);
      if ($goal === null) {
        $this->abortNotFound();
      }
      $updated = $model->update(\getPdo(), $goalId, $userId, $goalData);
      if (!$updated) {
        \setFlash(self::EDIT_ERROR_KEY, 'Изменения не были сохранены.');
        \redirect('/savings?period=' . rawurlencode($selectedPeriod));
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'goals_update', 'user_id' => $userId, 'goal_id' => $goalId]);
      $this->storeSessionArray(self::EDIT_OLD_INPUT_KEY, $oldInput);
      \setFlash(self::EDIT_ERROR_KEY, 'Не удалось обновить цель. Попробуйте позже.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::FORM_NOTICE_KEY, 'Цель обновлена.');
    \redirect('/savings?period=' . rawurlencode($selectedPeriod));
  }

  public function delete(): void
  {
    \requireAuth();
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $selectedPeriod = $this->normalizePeriod((string) (\query('period', self::DEFAULT_PERIOD) ?? self::DEFAULT_PERIOD));
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash(self::FORM_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    $goalId = $this->normalizePositiveInt((string) \input('goal_id', ''));
    if ($goalId === null) {
      $this->abortNotFound();
    }

    $returnFundsRaw = (string) \input('return_funds', '');
    $returnFundsParsed = $this->parseReturnFundsStrict($returnFundsRaw);
    if ($returnFundsParsed === null) {
      \setFlash(self::FORM_ERROR_KEY, 'Выберите, вернуть ли средства на баланс.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    try {
      $model = new Goal();
      $goal = $model->findForUser(\getPdo(), $goalId, $userId);
      if ($goal === null) {
        $this->abortNotFound();
      }
      $deleted = $model->delete(\getPdo(), $goalId, $userId, $returnFundsParsed);
      if (!$deleted) {
        \setFlash(self::FORM_ERROR_KEY, 'Не удалось удалить цель.');
        \redirect('/savings?period=' . rawurlencode($selectedPeriod));
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'goals_delete', 'user_id' => $userId, 'goal_id' => $goalId]);
      \setFlash(self::FORM_ERROR_KEY, 'Ошибка сервера при удалении цели.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::FORM_NOTICE_KEY, 'Цель удалена.');
    \redirect('/savings?period=' . rawurlencode($selectedPeriod));
  }

  public function contribute(): void
  {
    \requireAuth();
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $selectedPeriod = $this->normalizePeriod((string) (\query('period', self::DEFAULT_PERIOD) ?? self::DEFAULT_PERIOD));
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash(self::CONTRIBUTION_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    $goalId = $this->normalizePositiveInt((string) \input('goal_id', ''));
    if ($goalId === null) {
      $this->abortNotFound();
    }

    $amountRaw = (string) \input('amount', '');
    if (!is_numeric($amountRaw) || (float) $amountRaw <= 0) {
      $this->storeSessionArray(self::CONTRIBUTE_OLD_INPUT_KEY, [
        'goal_id' => (string) $goalId,
        'amount' => $amountRaw,
      ]);
      \setFlash(self::CONTRIBUTION_ERROR_KEY, 'Сумма пополнения должна быть больше нуля.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }
    $amount = (float) $amountRaw;

    try {
      $goalModel = new Goal();
      $goal = $goalModel->findForUser(\getPdo(), $goalId, $userId);
      if ($goal === null) {
        $this->abortNotFound();
      }

      $categoryModel = new Category();
      $savingsCategoryId = $categoryModel->getSavingsCategoryId(\getPdo());
      if ($savingsCategoryId === null) {
        \setFlash(self::CONTRIBUTION_ERROR_KEY, 'Не найдена системная категория "Накопления".');
        \redirect('/savings?period=' . rawurlencode($selectedPeriod));
      }

      $goalModel->addContribution(
        \getPdo(),
        $goalId,
        $userId,
        $amount,
        $savingsCategoryId,
        (string) ($goal['title'] ?? 'Без названия')
      );
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'goals_contribute', 'user_id' => $userId, 'goal_id' => $goalId]);
      $this->storeSessionArray(self::CONTRIBUTE_OLD_INPUT_KEY, [
        'goal_id' => (string) $goalId,
        'amount' => $amountRaw,
      ]);
      \setFlash(self::CONTRIBUTION_ERROR_KEY, 'Не удалось пополнить цель. Попробуйте позже.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::FORM_NOTICE_KEY, 'Цель пополнена.');
    \redirect('/savings?period=' . rawurlencode($selectedPeriod));
  }

  public function setStatus(): void
  {
    \requireAuth();
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $selectedPeriod = $this->normalizePeriod((string) (\query('period', self::DEFAULT_PERIOD) ?? self::DEFAULT_PERIOD));
    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash(self::FORM_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    $goalId = $this->normalizePositiveInt((string) \input('goal_id', ''));
    if ($goalId === null) {
      $this->abortNotFound();
    }

    $status = (string) \input('status', '');
    if (!$this->isAllowedManualStatus($status)) {
      \setFlash(self::FORM_ERROR_KEY, 'Недопустимый статус цели.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    $returnFunds = false;
    if ($status === Goal::STATUS_CANCELLED) {
      $returnFundsRaw = (string) \input('return_funds', '');
      $returnFundsParsed = $this->parseReturnFundsForCancel($returnFundsRaw);
      if ($returnFundsParsed === null) {
        \setFlash(self::FORM_ERROR_KEY, 'Выберите, вернуть ли средства на баланс.');
        \redirect('/savings?period=' . rawurlencode($selectedPeriod));
      }
      $returnFunds = $returnFundsParsed;
    }

    try {
      $model = new Goal();
      $goal = $model->findForUser(\getPdo(), $goalId, $userId);
      if ($goal === null) {
        $this->abortNotFound();
      }

      $updated = $model->setStatus(\getPdo(), $goalId, $userId, $status, $returnFunds);
      if (!$updated) {
        \setFlash(self::FORM_ERROR_KEY, 'Статус не изменён.');
        \redirect('/savings?period=' . rawurlencode($selectedPeriod));
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'goals_status', 'user_id' => $userId, 'goal_id' => $goalId]);
      \setFlash(self::FORM_ERROR_KEY, 'Не удалось изменить статус цели.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::FORM_NOTICE_KEY, 'Статус цели обновлён.');
    \redirect('/savings?period=' . rawurlencode($selectedPeriod));
  }

  /**
   * @param array<string, mixed> $goal
   * @return array<string, mixed>
   */
  private function prepareGoalForView(array $goal): array
  {
    $target = isset($goal['target_amount']) ? (float) $goal['target_amount'] : 0.0;
    $current = isset($goal['current_amount']) ? (float) $goal['current_amount'] : 0.0;
    $safeTarget = $target > 0 ? $target : 0.0;
    $rawPercent = $safeTarget > 0 ? ($current / $safeTarget) * 100 : 0.0;
    $progressPercent = max(0.0, min(100.0, $rawPercent));
    $deadline = isset($goal['deadline']) ? (string) $goal['deadline'] : '';
    $daysLeft = $this->calculateDaysLeft($deadline);

    $goal['target_amount'] = number_format($safeTarget, 2, '.', '');
    $goal['current_amount'] = number_format($current, 2, '.', '');
    $goal['progress_percent'] = number_format($progressPercent, 2, '.', '');
    $goal['days_left'] = $daysLeft;
    $goal['days_left_text'] = $daysLeft <= 0 ? self::DEADLINE_EXPIRED_TEXT : ('Осталось примерно ' . $daysLeft . ' дней');
    $goal['status'] = isset($goal['status']) ? (string) $goal['status'] : Goal::STATUS_ACTIVE;

    return $goal;
  }

  private function calculateDaysLeft(string $deadline): int
  {
    $deadlineDate = DateTimeImmutable::createFromFormat('Y-m-d', $deadline);
    if ($deadlineDate === false || $deadlineDate->format('Y-m-d') !== $deadline) {
      return 0;
    }

    $todayDate = new DateTimeImmutable('today');
    $interval = $todayDate->diff($deadlineDate);
    $days = (int) $interval->format('%r%a');

    return $days;
  }

  private function validateGoalInput(string $title, string $targetAmount, string $period, string $deadline): ?string
  {
    $trimmedTitle = trim($title);
    if ($trimmedTitle === '') {
      return 'Название цели обязательно.';
    }

    if (mb_strlen($trimmedTitle) > self::MAX_TITLE_LENGTH) {
      return 'Название цели не должно превышать 150 символов.';
    }

    if (!is_numeric($targetAmount) || (float) $targetAmount <= 0) {
      return 'Сумма цели должна быть больше нуля.';
    }

    if (!in_array($period, [self::PERIOD_WEEK, self::PERIOD_MONTH, self::PERIOD_YEAR], true)) {
      return 'Выберите корректный период.';
    }

    $deadlineDate = DateTimeImmutable::createFromFormat('Y-m-d', $deadline);
    if ($deadlineDate === false || $deadlineDate->format('Y-m-d') !== $deadline) {
      return 'Укажите корректную дату дедлайна.';
    }

    $todayDate = new DateTimeImmutable('today');
    if ($deadlineDate < $todayDate) {
      return 'Дата дедлайна не может быть раньше сегодняшней.';
    }

    return null;
  }

  private function ensureCsrfToken(): string
  {
    \ensureSessionStarted();
    $token = $_SESSION[self::CSRF_SESSION_KEY] ?? null;
    if (!is_string($token) || $token === '') {
      $token = bin2hex(random_bytes(32));
      $_SESSION[self::CSRF_SESSION_KEY] = $token;
    }

    return $token;
  }

  private function isValidCsrfToken(string $token): bool
  {
    \ensureSessionStarted();
    $sessionToken = $_SESSION[self::CSRF_SESSION_KEY] ?? '';

    return $token !== '' && is_string($sessionToken) && $sessionToken !== '' && hash_equals($sessionToken, $token);
  }

  /**
   * @param array<string, string> $input
   */
  private function storeOldInput(array $input): void
  {
    \ensureSessionStarted();
    $_SESSION[self::FORM_OLD_INPUT_KEY] = $input;
  }

  /**
   * @return array<string, string>
   */
  private function takeOldInput(): array
  {
    \ensureSessionStarted();
    $raw = $_SESSION[self::FORM_OLD_INPUT_KEY] ?? null;
    unset($_SESSION[self::FORM_OLD_INPUT_KEY]);

    if (!is_array($raw)) {
      return [];
    }

    $result = [];
    foreach ($raw as $key => $value) {
      if (!is_string($key) || !is_string($value)) {
        continue;
      }
      $result[$key] = $value;
    }

    return $result;
  }

  private function normalizePeriod(string $period): string
  {
    return match ($period) {
      self::PERIOD_WEEK => self::PERIOD_WEEK,
      self::PERIOD_YEAR => self::PERIOD_YEAR,
      default => self::PERIOD_MONTH,
    };
  }

  private function isAllowedManualStatus(string $status): bool
  {
    return in_array($status, [Goal::STATUS_ACTIVE, self::STATUS_CANCELLED], true);
  }

  /**
   * @param array<string, string> $input
   */
  private function storeSessionArray(string $key, array $input): void
  {
    \ensureSessionStarted();
    $_SESSION[$key] = $input;
  }

  /**
   * @return array<string, string>
   */
  private function takeSessionArray(string $key): array
  {
    \ensureSessionStarted();
    $raw = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);

    if (!is_array($raw)) {
      return [];
    }

    $result = [];
    foreach ($raw as $itemKey => $itemValue) {
      if (!is_string($itemKey) || !is_string($itemValue)) {
        continue;
      }
      $result[$itemKey] = $itemValue;
    }

    return $result;
  }

  private function normalizePositiveInt(string $value): ?int
  {
    if (!ctype_digit($value)) {
      return null;
    }
    $id = (int) $value;
    return $id > 0 ? $id : null;
  }

  private function parseReturnFundsStrict(string $value): ?bool
  {
    return match ($value) {
      '1' => true,
      '0' => false,
      default => null,
    };
  }

  /**
   * Для отмены цели: «1» / «0» / пусто (по умолчанию не возвращать).
   */
  private function parseReturnFundsForCancel(string $value): ?bool
  {
    if ($value === '') {
      return false;
    }

    return $this->parseReturnFundsStrict($value);
  }

  private function abortNotFound(): void
  {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    exit;
  }

  /**
   * @return array<string, string>
   */
  private function takePurchasePlanOldInput(): array
  {
    \ensureSessionStarted();
    $raw = $_SESSION[self::PURCHASE_PLAN_OLD_INPUT_KEY] ?? null;
    unset($_SESSION[self::PURCHASE_PLAN_OLD_INPUT_KEY]);

    if (!is_array($raw)) {
      return [];
    }

    $result = [];
    foreach ($raw as $key => $value) {
      if (!is_string($key) || !is_string($value)) {
        continue;
      }
      $result[$key] = $value;
    }

    return $result;
  }

  /**
   * @param array<int, array<string, mixed>> $rawPlans
   * @return array<int, array<string, mixed>>
   */
  private function preparePurchasePlansForView(array $rawPlans): array
  {
    $service = new PurchaseStrategy();
    $out = [];
    foreach ($rawPlans as $plan) {
      if (!is_array($plan)) {
        continue;
      }

      $id = isset($plan['id']) ? (int) $plan['id'] : 0;
      $target = isset($plan['target_amount']) ? (float) $plan['target_amount'] : 0.0;
      $title = isset($plan['title']) ? trim((string) $plan['title']) : '';
      $termMonths = isset($plan['term_months']) ? (int) $plan['term_months'] : 12;
      if ($termMonths < 1) {
        $termMonths = 12;
      }

      if ($id <= 0 || $title === '') {
        continue;
      }

      $result = $service->calculate($target, $termMonths);
      $strategies = isset($result['strategies']) && is_array($result['strategies']) ? $result['strategies'] : [];
      $strategiesForView = [];
      foreach ($strategies as $row) {
        if (!is_array($row)) {
          continue;
        }
        $strategiesForView[] = $this->mapPurchaseStrategyToStrategyCard($row);
      }

      $out[] = [
        'id' => $id,
        'title' => $title,
        'term_months' => $termMonths,
        'target_amount_formatted' => number_format($target, 2, '.', ' '),
        'strategies' => $strategiesForView,
      ];
    }

    return $out;
  }

  /**
   * @param array<string, mixed> $s
   * @return array<string, mixed>
   */
  private function mapPurchaseStrategyToStrategyCard(array $s): array
  {
    $targetDateRaw = isset($s['target_date']) ? (string) $s['target_date'] : '';
    $dateObj = DateTimeImmutable::createFromFormat('Y-m-d', $targetDateRaw);
    $dateFormatted = $dateObj !== false && $dateObj->format('Y-m-d') === $targetDateRaw
      ? $dateObj->format('d.m.Y')
      : $targetDateRaw;

    return [
      'label' => (string) ($s['label'] ?? ''),
      'emoji' => (string) ($s['emoji'] ?? ''),
      'description' => '',
      'months' => isset($s['months']) ? (int) $s['months'] : 0,
      'monthly_payment_formatted' => number_format((float) ($s['monthly_amount'] ?? 0), 2, '.', ' '),
      'overpayment_formatted' => '0.00',
      'payment_dt_label' => 'Ежемесячно',
      'third_dt_label' => 'Дата достижения',
      'third_dd_value' => $dateFormatted,
      'third_dd_plain' => true,
    ];
  }
}
