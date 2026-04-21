<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/functions.php';

use App\Models\Goal;
use App\Models\PurchasePlan;
use App\Services\PurchaseStrategy;
use Throwable;

final class PurchasePlanController
{
  private const CSRF_SESSION_KEY = 'goals_csrf';
  private const FORM_OLD_INPUT_KEY = 'purchase_plan_old_input';
  private const FORM_ERROR_KEY = 'purchase_plan_form_error';
  private const GOALS_FORM_NOTICE_KEY = 'goals_form_notice';
  private const MAX_TITLE_LENGTH = 150;
  private const DEFAULT_PERIOD = 'month';

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
    $termMonthsRaw = (string) \input('term_months', '');

    $oldInput = [
      'title' => $title,
      'target_amount' => $targetAmount,
      'term_months' => $termMonthsRaw,
    ];

    $validationError = $this->validatePlanInput($title, $targetAmount, $termMonthsRaw);
    if ($validationError !== null) {
      $this->storeOldInput($oldInput);
      \setFlash(self::FORM_ERROR_KEY, $validationError);
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    $termMonths = (int) $termMonthsRaw;

    $data = [
      'user_id' => $userId,
      'title' => trim($title),
      'target_amount' => number_format((float) $targetAmount, 2, '.', ''),
      'term_months' => $termMonths,
    ];

    try {
      $model = new PurchasePlan();
      $id = $model->create(\getPdo(), $data);
      if ($id <= 0) {
        throw new \RuntimeException('Purchase plan was not created.');
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'purchase_plan_create', 'user_id' => $userId]);
      $this->storeOldInput($oldInput);
      \setFlash(self::FORM_ERROR_KEY, 'Не удалось сохранить план. Попробуйте позже.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    \redirect('/savings?period=' . rawurlencode($selectedPeriod));
  }

  public function convert(): void
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

    $planId = $this->normalizePositiveInt((string) \input('plan_id', ''));
    if ($planId === null) {
      $this->abortNotFound();
    }

    $pdo = \getPdo();
    $purchasePlanModel = new PurchasePlan();
    $goalModel = new Goal();

    $pdo->beginTransaction();

    try {
      $plan = $purchasePlanModel->findForUser($pdo, $planId, $userId);
      if ($plan === null) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $this->abortNotFound();
      }

      $title = trim((string) ($plan['title'] ?? ''));
      $targetAmountRaw = $plan['target_amount'] ?? '0';
      $termMonths = (int) ($plan['term_months'] ?? 0);

      if ($title === '' || !is_numeric((string) $targetAmountRaw) || (float) $targetAmountRaw <= 0) {
        throw new \RuntimeException('Purchase plan has invalid data for conversion.');
      }

      if (!in_array($termMonths, PurchaseStrategy::AVAILABLE_TERMS, true)) {
        throw new \RuntimeException('Purchase plan has invalid term.');
      }

      $goalData = [
        'user_id' => $userId,
        'title' => $title,
        'target_amount' => number_format((float) $targetAmountRaw, 2, '.', ''),
        'term_months' => $termMonths,
      ];

      $goalId = $goalModel->createFromPlan($pdo, $goalData);
      if ($goalId <= 0) {
        throw new \RuntimeException('Goal was not created from plan.');
      }

      $deleted = $purchasePlanModel->delete($pdo, $planId, $userId);
      if (!$deleted) {
        throw new \RuntimeException('Purchase plan was not deleted after goal creation.');
      }

      $pdo->commit();
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      log_exception($e, ['action' => 'purchase_plan_convert', 'user_id' => $userId, 'plan_id' => $planId]);
      \setFlash(self::FORM_ERROR_KEY, 'Не удалось перенести план в цели. Попробуйте позже.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::GOALS_FORM_NOTICE_KEY, 'Цель добавлена');
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

    $planId = $this->normalizePositiveInt((string) \input('plan_id', ''));
    if ($planId === null) {
      $this->abortNotFound();
    }

    try {
      $model = new PurchasePlan();
      $deleted = $model->delete(\getPdo(), $planId, $userId);
      if (!$deleted) {
        $this->abortNotFound();
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'purchase_plan_delete', 'user_id' => $userId, 'plan_id' => $planId]);
      \setFlash(self::FORM_ERROR_KEY, 'Не удалось удалить план.');
      \redirect('/savings?period=' . rawurlencode($selectedPeriod));
    }

    \redirect('/savings?period=' . rawurlencode($selectedPeriod));
  }

  private function validatePlanInput(string $title, string $targetAmount, string $termMonthsRaw): ?string
  {
    $trimmedTitle = trim($title);
    if ($trimmedTitle === '') {
      return 'Укажите название покупки.';
    }

    if (mb_strlen($trimmedTitle) > self::MAX_TITLE_LENGTH) {
      return 'Название не должно превышать 150 символов.';
    }

    if (!is_numeric($targetAmount) || (float) $targetAmount <= 0) {
      return 'Сумма должна быть больше нуля.';
    }

    if (!ctype_digit($termMonthsRaw)) {
      return 'Выберите срок накопления.';
    }

    $termMonths = (int) $termMonthsRaw;
    if (!in_array($termMonths, PurchaseStrategy::AVAILABLE_TERMS, true)) {
      return 'Недопустимый срок накопления.';
    }

    return null;
  }

  private function normalizePeriod(string $period): string
  {
    return match ($period) {
      'week' => 'week',
      'year' => 'year',
      default => 'month',
    };
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

  private function normalizePositiveInt(string $value): ?int
  {
    if (!ctype_digit($value)) {
      return null;
    }
    $id = (int) $value;

    return $id > 0 ? $id : null;
  }

  private function abortNotFound(): void
  {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    exit;
  }
}
