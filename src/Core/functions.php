<?php

declare(strict_types=1);

/**
 * Загрузка переменных из `.env` в `$_ENV` и `putenv` (для `getenv`).
 */
function loadEnv(string $path): void
{
  if (!is_readable($path)) {
    throw new RuntimeException("Файл окружения не найден или недоступен: {$path}");
  }

  foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
      continue;
    }
    if (!str_contains($line, '=')) {
      continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    $value = trim($value, '"\''); // срезает " и ' с обоих концов
    $_ENV[$key] = $value;
    putenv("{$key}={$value}");
  }
}

/**
 * Значение из окружения. При отсутствии ключа — исключение или `$default`.
 */
function env(string $key, ?string $default = null): string
{
  $value = $_ENV[$key] ?? getenv($key);
  if ($value === false || $value === null || $value === '') {
    if ($default !== null) {
      return $default;
    }
    throw new RuntimeException("Отсутствует переменная окружения: {$key}");
  }
  return $value;
}

/**
 * Запускает сессию, если она ещё не активна.
 */
function ensureSessionStarted(): void
{
  if (session_status() === PHP_SESSION_ACTIVE) {
    return;
  }

  if (!headers_sent()) {
    session_start();
  }
}

/**
 * Редирект на указанный путь.
 */
function redirect(string $path): void
{
  header("Location: {$path}");
  exit;
}

/**
 * Проверяет, что пользователь авторизован.
 */
function isAuthenticated(): bool
{
  ensureSessionStarted();
  $userId = $_SESSION['user_id'] ?? null;
  return normalizeUserId($userId) !== null;
}

/**
 * Требует авторизацию для доступа к маршруту.
 */
function requireAuth(): void
{
  if (isAuthenticated()) {
    return;
  }

  redirect('/login');
}

/**
 * Редиректит авторизованного пользователя на дашборд.
 */
function redirectIfAuthenticated(): void
{
  if (!isAuthenticated()) {
    return;
  }

  redirect('/dashboard');
}

/**
 * Сохраняет flash-сообщение на один запрос.
 */
function setFlash(string $key, string $message): void
{
  ensureSessionStarted();
  $_SESSION['flash'][$key] = $message;
}

/**
 * Возвращает flash-сообщение и удаляет его из сессии.
 */
function getFlash(string $key): ?string
{
  ensureSessionStarted();
  $flash = $_SESSION['flash'][$key] ?? null;

  if (!is_string($flash) || $flash === '') {
    return null;
  }

  unset($_SESSION['flash'][$key]);
  return $flash;
}

/**
 * Приводит значение user_id к положительному int или null.
 */
function normalizeUserId(mixed $value): ?int
{
  if (is_int($value) && $value > 0) {
    return $value;
  }

  if (is_string($value) && ctype_digit($value)) {
    $intValue = (int) $value;
    return $intValue > 0 ? $intValue : null;
  }

  return null;
}

/**
 * Инициалы для аватара: два первых символа по словам или до двух букв из одного слова.
 */
function userInitialsFromFullName(string $name): string
{
  $trimmed = trim($name);
  if ($trimmed === '') {
    return '?';
  }

  $parts = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
  if ($parts === false || $parts === []) {
    return mb_strtoupper(mb_substr($trimmed, 0, 1));
  }

  if (count($parts) >= 2) {
    $first = mb_substr($parts[0], 0, 1);
    $second = mb_substr($parts[1], 0, 1);

    return mb_strtoupper($first . $second);
  }

  $single = $parts[0];
  $len = mb_strlen($single);

  return mb_strtoupper(mb_substr($single, 0, min(2, $len)));
}

/**
 * Рендерит php-шаблон из src/Views с переданными данными.
 */
function render(string $view, array $data = []): void
{
  if ($view === '') {
    throw new RuntimeException('Имя шаблона не может быть пустым.');
  }

  if (str_contains($view, '..') || str_contains($view, '\\') || str_ends_with($view, '.php')) {
    throw new RuntimeException("Некорректное имя шаблона: {$view}");
  }

  $normalizedView = trim($view, '/');
  if ($normalizedView === '') {
    throw new RuntimeException('Имя шаблона не может содержать только слеши.');
  }

  $viewsPath = ROOT_PATH . '/src/Views';
  $viewPath = $viewsPath . '/' . $normalizedView . '.php';

  if (!is_file($viewPath)) {
    throw new RuntimeException("Шаблон не найден: {$view}. Ожидался путь: {$viewPath}");
  }

  extract($data, EXTR_SKIP);
  require $viewPath;
}
