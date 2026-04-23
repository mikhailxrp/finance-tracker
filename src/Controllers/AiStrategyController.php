<?php

declare(strict_types=1);

namespace App\Controllers;

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/HttpClient.php';
require_once dirname(__DIR__) . '/Core/functions.php';

use App\Models\AiStrategy;
use Throwable;

final class AiStrategyController
{
  private const DEFAULT_PERIOD = 'all';
  private const PERIOD_ALL = 'all';
  private const PERIOD_WEEK = 'week';
  private const PERIOD_MONTH = 'month';
  private const PERIOD_YEAR = 'year';
  private const CSRF_SESSION_KEY = 'strategy_csrf';
  private const FORM_ERROR_KEY = 'strategy_form_error';
  private const FORM_SUCCESS_KEY = 'strategy_form_success';
  private const FORM_OLD_INPUT_KEY = 'strategy_form_old_input';
  private const INSUFFICIENT_DATA_KEY = 'strategy_insufficient_data';
  private const N8N_TIMEOUT_SECONDS = 360;
  private const MESSAGE_MAX_LENGTH = 2000;

  private const STRATEGY_PRESETS = [
    'Оптимизируй мои расходы за последний месяц',
    'Как мне быстрее накопить на текущую цель',
    'Разбери мой бюджет и дай рекомендации',
    'Помоги спланировать погашение кредита',
  ];

  public function index(): void
  {
    \requireAuth();

    $profile = \getAuthenticatedUserProfile();
    if ($profile === null) {
      \redirect('/login');
    }

    $selectedPeriod = $this->normalizePeriod((string) (\query('period', self::DEFAULT_PERIOD) ?? self::DEFAULT_PERIOD));
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $csrf = $this->ensureCsrfToken();
    $formError = \getFlash(self::FORM_ERROR_KEY);
    $formSuccess = \getFlash(self::FORM_SUCCESS_KEY);
    $oldInput = $this->takeOldInput();
    $insufficientData = $this->takeInsufficientData();
    $latestStrategy = null;
    $strategyHistory = [];
    $hasHistory = false;

    try {
      $strategyModel = new AiStrategy();
      $latestStrategy = $strategyModel->findLatestForUser(\getPdo(), $userId);
      $strategyHistory = $strategyModel->getAllForUser(\getPdo(), $userId, $selectedPeriod);
      $hasHistory = $selectedPeriod === self::PERIOD_ALL
        ? $strategyHistory !== []
        : $strategyModel->getAllForUser(\getPdo(), $userId, self::PERIOD_ALL) !== [];
    } catch (Throwable $e) {
      log_exception($e, ['action' => 'strategy_index', 'user_id' => $userId]);
      if ($formError === null || $formError === '') {
        $formError = 'Не удалось загрузить историю стратегий. Попробуйте позже.';
      }
    }

    header('Content-Type: text/html; charset=utf-8');
    \render('strategy', [
      'userName' => $profile['name'],
      'userEmail' => $profile['email'],
      'currentPath' => '/strategy',
      'selectedPeriod' => $selectedPeriod,
      'csrf' => $csrf,
      'formError' => $formError,
      'formSuccess' => $formSuccess,
      'oldInput' => $oldInput,
      'insufficientData' => $insufficientData,
      'latestStrategy' => $latestStrategy,
      'strategyPresets' => self::STRATEGY_PRESETS,
      'strategyHistory' => $strategyHistory,
      'hasHistory' => $hasHistory,
    ]);
  }

  public function generate(): void
  {
    \requireAuth();
    $userId = \normalizeUserId($_SESSION['user_id'] ?? null);
    if ($userId === null) {
      \redirect('/login');
    }

    $csrf = (string) \input('csrf', '');
    if (!$this->isValidCsrfToken($csrf)) {
      \setFlash(self::FORM_ERROR_KEY, 'Сессия устарела. Обновите страницу и попробуйте снова.');
      \redirect('/strategy');
    }

    $message = trim((string) \input('message', ''));
    $validationError = $this->validateMessage($message);
    if ($validationError !== null) {
      $this->storeOldInput(['message' => $message]);
      \setFlash(self::FORM_ERROR_KEY, $validationError);
      \redirect('/strategy');
    }

    $webhookUrl = defined('N8N_WEBHOOK_URL') ? (string) N8N_WEBHOOK_URL : '';
    if ($webhookUrl === '') {
      log_error('N8N webhook URL is not configured.', ['action' => 'strategy_generate', 'user_id' => $userId]);
      \setFlash(self::FORM_ERROR_KEY, 'Сервис временно недоступен, попробуйте ещё раз.');
      \redirect('/strategy');
    }

    $payload = [
      'user_id' => $userId,
      'message' => $message,
    ];

    $response = \postJson(
      $webhookUrl,
      $payload,
      self::N8N_TIMEOUT_SECONDS
    );

    $responseBody = is_string($response['body'] ?? null) ? trim((string) $response['body']) : '';
    $responseJson = $this->normalizeN8nResponse($responseBody);
    $isHttpSuccessful = ($response['ok'] ?? false) === true
      && (int) ($response['status'] ?? 0) === 200
      && $responseJson !== null;

    if (!$isHttpSuccessful) {
      log_error('n8n strategy request failed.', [
        'action' => 'strategy_generate',
        'user_id' => $userId,
        'status' => (int) ($response['status'] ?? 0),
        'error' => $response['error'] ?? null,
        'response_preview' => mb_substr($responseBody, 0, 300),
      ]);
      \setFlash(self::FORM_ERROR_KEY, 'Сервис временно недоступен, попробуйте ещё раз.');
      \redirect('/strategy');
    }

    $isSuccess = ($responseJson['success'] ?? false) === true;
    if (!$isSuccess) {
      $errorCode = isset($responseJson['error']) ? (string) $responseJson['error'] : '';
      if ($errorCode === 'insufficient_data') {
        $this->storeInsufficientData($responseJson);
        \redirect('/strategy');
      }

      log_error('n8n strategy request returned success=false.', [
        'action' => 'strategy_generate',
        'user_id' => $userId,
        'error_code' => $errorCode,
        'response_preview' => mb_substr($responseBody, 0, 300),
      ]);
      \setFlash(self::FORM_ERROR_KEY, 'Сервис временно недоступен, попробуйте ещё раз.');
      \redirect('/strategy');
    }

    $this->clearOldInput();
    \setFlash(self::FORM_SUCCESS_KEY, 'Стратегия успешно сформирована.');
    \redirect('/strategy');
  }

  private function normalizePeriod(string $period): string
  {
    return match ($period) {
      self::PERIOD_ALL => self::PERIOD_ALL,
      self::PERIOD_WEEK => self::PERIOD_WEEK,
      self::PERIOD_MONTH => self::PERIOD_MONTH,
      self::PERIOD_YEAR => self::PERIOD_YEAR,
      default => self::PERIOD_ALL,
    };
  }

  private function validateMessage(string $message): ?string
  {
    if ($message === '') {
      return 'Введите запрос для генерации стратегии.';
    }

    $messageLength = mb_strlen($message);
    if ($messageLength < 1 || $messageLength > self::MESSAGE_MAX_LENGTH) {
      return 'Длина запроса должна быть от 1 до 2000 символов.';
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

  private function clearOldInput(): void
  {
    \ensureSessionStarted();
    unset($_SESSION[self::FORM_OLD_INPUT_KEY]);
  }

  /**
   * @return array<string, mixed>|null
   */
  private function normalizeN8nResponse(string $responseBody): ?array
  {
    if ($responseBody === '') {
      return null;
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
      return null;
    }

    if (array_is_list($decoded)) {
      $first = $decoded[0] ?? null;
      return is_array($first) ? $first : null;
    }

    return $decoded;
  }

  /**
   * @param array<string, mixed> $responseJson
   */
  private function storeInsufficientData(array $responseJson): void
  {
    $message = isset($responseJson['message']) ? (string) $responseJson['message'] : '';
    $suggestions = isset($responseJson['suggestions']) && is_array($responseJson['suggestions'])
      ? $responseJson['suggestions']
      : [];
    $currentData = isset($responseJson['current_data']) && is_array($responseJson['current_data'])
      ? $responseJson['current_data']
      : [];

    \ensureSessionStarted();
    $_SESSION[self::INSUFFICIENT_DATA_KEY] = [
      'message' => $message,
      'suggestions' => $suggestions,
      'current_data' => $currentData,
    ];
  }

  /**
   * @return array<string, mixed>
   */
  private function takeInsufficientData(): array
  {
    \ensureSessionStarted();
    $raw = $_SESSION[self::INSUFFICIENT_DATA_KEY] ?? null;
    unset($_SESSION[self::INSUFFICIENT_DATA_KEY]);

    return is_array($raw) ? $raw : [];
  }
}
