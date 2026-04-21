<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/functions.php';

use App\Models\Credit;
use App\Services\CreditCalc;
use DateTimeImmutable;
use Throwable;

final class CreditController
{
  private const CSRF_SESSION_KEY = 'credits_csrf';
  private const FORM_OLD_INPUT_KEY = 'credits_old_input';
  private const FORM_ERROR_KEY = 'credits_form_error';
  private const FORM_NOTICE_KEY = 'credits_form_notice';
  private const EDIT_OLD_INPUT_KEY = 'credits_edit_old_input';
  private const EDIT_ERROR_KEY = 'credits_edit_error';
  private const MAX_TITLE_LENGTH = 150;
  private const MAX_INTEREST_RATE = 200.0;
  private const DEFAULT_PERIOD = 'month';

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
    $error = \getFlash(self::FORM_ERROR_KEY);
    $notice = \getFlash(self::FORM_NOTICE_KEY);
    $oldInput = $this->takeOldInput();
    $editError = \getFlash(self::EDIT_ERROR_KEY);
    $editOldInput = $this->takeSessionArray(self::EDIT_OLD_INPUT_KEY);
    $csrf = $this->ensureCsrfToken();

    $activeCredits = [];
    $closedCredits = [];

    try {
      $model = new Credit();
      $rows = $model->getAllForUser(\getPdo(), $userId);
      foreach ($rows as $row) {
        if (!is_array($row)) {
          continue;
        }
        $status = isset($row['status']) ? (string) $row['status'] : Credit::STATUS_ACTIVE;
        $includeStrategies = $status === Credit::STATUS_ACTIVE;
        $prepared = $this->prepareCreditForView($row, $includeStrategies);
        if ($status === Credit::STATUS_CLOSED) {
          $closedCredits[] = $prepared;
        } else {
          $activeCredits[] = $prepared;
        }
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'credits_index', 'user_id' => $userId]);
      $error = 'Не удалось загрузить кредиты. Попробуйте позже.';
      $activeCredits = [];
      $closedCredits = [];
    }

    header('Content-Type: text/html; charset=utf-8');
    \render('credits', [
      'userName' => $profile['name'],
      'userEmail' => $profile['email'],
      'currentPath' => '/credits',
      'selectedPeriod' => $selectedPeriod,
      'activeCredits' => $activeCredits,
      'closedCredits' => $closedCredits,
      'csrf' => $csrf,
      'formError' => $error,
      'formNotice' => $notice,
      'oldInput' => $oldInput,
      'editError' => $editError,
      'editOldInput' => $editOldInput,
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
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    $title = (string) \input('title', '');
    $totalAmount = (string) \input('total_amount', '');
    $interestRate = (string) \input('interest_rate', '');
    $startDate = (string) \input('start_date', '');

    $oldInput = [
      'title' => $title,
      'total_amount' => $totalAmount,
      'interest_rate' => $interestRate,
      'start_date' => $startDate,
    ];

    $validationError = $this->validateCreditInput($title, $totalAmount, $interestRate, $startDate);
    if ($validationError !== null) {
      $this->storeOldInput($oldInput);
      \setFlash(self::FORM_ERROR_KEY, $validationError);
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    $data = [
      'user_id' => $userId,
      'title' => trim($title),
      'total_amount' => number_format((float) $totalAmount, 2, '.', ''),
      'interest_rate' => number_format((float) $interestRate, 2, '.', ''),
      'start_date' => $startDate,
    ];

    try {
      $model = new Credit();
      $id = $model->create(\getPdo(), $data);
      if ($id <= 0) {
        throw new \RuntimeException('Credit was not created.');
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'credits_create', 'user_id' => $userId]);
      $this->storeOldInput($oldInput);
      \setFlash(self::FORM_ERROR_KEY, 'Не удалось создать кредит. Попробуйте позже.');
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::FORM_NOTICE_KEY, 'Кредит добавлен.');
    \redirect('/credits?period=' . rawurlencode($selectedPeriod));
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
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    $creditId = $this->normalizePositiveInt((string) \input('credit_id', ''));
    if ($creditId === null) {
      $this->abortNotFound();
    }

    $title = (string) \input('title', '');
    $totalAmount = (string) \input('total_amount', '');
    $interestRate = (string) \input('interest_rate', '');
    $startDate = (string) \input('start_date', '');

    $oldInput = [
      'credit_id' => (string) $creditId,
      'title' => $title,
      'total_amount' => $totalAmount,
      'interest_rate' => $interestRate,
      'start_date' => $startDate,
    ];

    $error = $this->validateCreditInput($title, $totalAmount, $interestRate, $startDate);
    if ($error !== null) {
      $this->storeSessionArray(self::EDIT_OLD_INPUT_KEY, $oldInput);
      \setFlash(self::EDIT_ERROR_KEY, $error);
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    $creditData = [
      'title' => trim($title),
      'total_amount' => number_format((float) $totalAmount, 2, '.', ''),
      'interest_rate' => number_format((float) $interestRate, 2, '.', ''),
      'start_date' => $startDate,
    ];

    try {
      $model = new Credit();
      $credit = $model->findForUser(\getPdo(), $creditId, $userId);
      if ($credit === null) {
        $this->abortNotFound();
      }
      $updated = $model->update(\getPdo(), $creditId, $userId, $creditData);
      if (!$updated) {
        \setFlash(self::EDIT_ERROR_KEY, 'Изменения не были сохранены.');
        \redirect('/credits?period=' . rawurlencode($selectedPeriod));
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'credits_update', 'user_id' => $userId, 'credit_id' => $creditId]);
      $this->storeSessionArray(self::EDIT_OLD_INPUT_KEY, $oldInput);
      \setFlash(self::EDIT_ERROR_KEY, 'Не удалось обновить кредит. Попробуйте позже.');
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::FORM_NOTICE_KEY, 'Кредит обновлён.');
    \redirect('/credits?period=' . rawurlencode($selectedPeriod));
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
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    $creditId = $this->normalizePositiveInt((string) \input('credit_id', ''));
    if ($creditId === null) {
      $this->abortNotFound();
    }

    try {
      $model = new Credit();
      $credit = $model->findForUser(\getPdo(), $creditId, $userId);
      if ($credit === null) {
        $this->abortNotFound();
      }
      $deleted = $model->delete(\getPdo(), $creditId, $userId);
      if (!$deleted) {
        \setFlash(self::FORM_ERROR_KEY, 'Не удалось удалить кредит.');
        \redirect('/credits?period=' . rawurlencode($selectedPeriod));
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'credits_delete', 'user_id' => $userId, 'credit_id' => $creditId]);
      \setFlash(self::FORM_ERROR_KEY, 'Ошибка сервера при удалении кредита.');
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::FORM_NOTICE_KEY, 'Кредит удалён.');
    \redirect('/credits?period=' . rawurlencode($selectedPeriod));
  }

  public function close(): void
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
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    $creditId = $this->normalizePositiveInt((string) \input('credit_id', ''));
    if ($creditId === null) {
      $this->abortNotFound();
    }

    try {
      $model = new Credit();
      $credit = $model->findForUser(\getPdo(), $creditId, $userId);
      if ($credit === null) {
        $this->abortNotFound();
      }
      $updated = $model->setStatus(\getPdo(), $creditId, $userId, Credit::STATUS_CLOSED);
      if (!$updated) {
        \setFlash(self::FORM_ERROR_KEY, 'Статус не изменён.');
        \redirect('/credits?period=' . rawurlencode($selectedPeriod));
      }
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'credits_close', 'user_id' => $userId, 'credit_id' => $creditId]);
      \setFlash(self::FORM_ERROR_KEY, 'Не удалось закрыть кредит.');
      \redirect('/credits?period=' . rawurlencode($selectedPeriod));
    }

    \setFlash(self::FORM_NOTICE_KEY, 'Кредит отмечен как закрытый.');
    \redirect('/credits?period=' . rawurlencode($selectedPeriod));
  }

  /**
   * @param array<string, mixed> $credit
   * @return array<string, mixed>
   */
  private function prepareCreditForView(array $credit, bool $includeStrategies): array
  {
    $total = isset($credit['total_amount']) ? (float) $credit['total_amount'] : 0.0;
    $rate = isset($credit['interest_rate']) ? (float) $credit['interest_rate'] : 0.0;

    $credit['total_amount_formatted'] = number_format($total, 2, '.', ' ');
    $credit['interest_rate_formatted'] = number_format($rate, 2, '.', '');
    $credit['strategies'] = $includeStrategies ? $this->buildStrategies($total, $rate) : [];

    return $credit;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function buildStrategies(float $total, float $annualRate): array
  {
    $calc = new CreditCalc();
    $defs = [
      [
        'id' => 'aggressive',
        'label' => 'Агрессивный',
        'description' => 'высокий платёж, минимальная переплата',
        'emoji' => '🚀',
        'months' => CreditCalc::TERM_AGGRESSIVE,
      ],
      [
        'id' => 'optimal',
        'label' => 'Оптимальный',
        'description' => 'баланс платежа и переплаты',
        'emoji' => '⚖️',
        'months' => CreditCalc::TERM_OPTIMAL,
      ],
      [
        'id' => 'minimal',
        'label' => 'Минимальный',
        'description' => 'низкий платёж, высокая переплата',
        'emoji' => '🌿',
        'months' => CreditCalc::TERM_MINIMAL,
      ],
    ];

    $out = [];
    foreach ($defs as $def) {
      $months = (int) $def['months'];
      $result = $calc->calculateAnnuity($total, $annualRate, $months);
      $monthly = (float) $result['monthly_payment'];
      $over = (float) $result['overpayment'];
      $out[] = [
        'id' => $def['id'],
        'label' => $def['label'],
        'description' => $def['description'],
        'emoji' => $def['emoji'],
        'months' => $months,
        'monthly_payment' => $monthly,
        'monthly_payment_formatted' => number_format($monthly, 2, '.', ' '),
        'overpayment' => $over,
        'overpayment_formatted' => number_format($over, 2, '.', ' '),
      ];
    }

    return $out;
  }

  private function validateCreditInput(
    string $title,
    string $totalAmount,
    string $interestRate,
    string $startDate
  ): ?string {
    $trimmedTitle = trim($title);
    if ($trimmedTitle === '') {
      return 'Название кредита обязательно.';
    }

    if (mb_strlen($trimmedTitle) > self::MAX_TITLE_LENGTH) {
      return 'Название не должно превышать 150 символов.';
    }

    if (!is_numeric($totalAmount) || (float) $totalAmount <= 0) {
      return 'Сумма кредита должна быть больше нуля.';
    }

    if (!is_numeric($interestRate)) {
      return 'Укажите процентную ставку числом.';
    }
    $rate = (float) $interestRate;
    if ($rate < 0 || $rate > self::MAX_INTEREST_RATE) {
      return 'Ставка должна быть от 0 до 200% годовых.';
    }

    $start = DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
    if ($start === false || $start->format('Y-m-d') !== $startDate) {
      return 'Укажите корректную дату начала кредита.';
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
      'week' => 'week',
      'year' => 'year',
      default => self::DEFAULT_PERIOD,
    };
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

  private function abortNotFound(): void
  {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo '404 Not Found';
    exit;
  }
}
