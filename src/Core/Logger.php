<?php

declare(strict_types=1);

/**
 * Файловый лог приложения.
 *
 * Формат строки: `[ISO-8601] LEVEL сообщение | JSON-контекст`
 * Каталог и имя файла — константы `LOG_DIR`, `LOG_FILE` из `config/config.php`
 * (переопределяются через `.env`: `LOG_DIR`, `LOG_FILE`, `APP_LOG_LEVEL`).
 *
 * Минимальный уровень записи: `APP_LOG_LEVEL` — debug|info|warning|error (по умолчанию error).
 */

/**
 * @var list<string>
 */
const LOG_SENSITIVE_KEY_FRAGMENTS = [
  'password',
  'passwd',
  'secret',
  'token',
  'authorization',
  'cookie',
  'csrf',
  'session',
];

function log_level_weight(string $level): int
{
  return match (strtolower($level)) {
    'debug' => 10,
    'info' => 20,
    'warning' => 30,
    'error' => 40,
    default => 40,
  };
}

function app_log_min_weight(): int
{
  if (!defined('APP_LOG_LEVEL')) {
    return log_level_weight('error');
  }

  return log_level_weight((string) APP_LOG_LEVEL);
}

function should_log(string $messageLevel): bool
{
  return log_level_weight($messageLevel) >= app_log_min_weight();
}

function sanitize_log_context(array $context): array
{
  $out = [];

  foreach ($context as $key => $value) {
    $keyLower = strtolower((string) $key);
    foreach (LOG_SENSITIVE_KEY_FRAGMENTS as $fragment) {
      if (str_contains($keyLower, $fragment)) {
        continue 2;
      }
    }

    if ($value instanceof Throwable) {
      $out[$key] = [
        'class' => $value::class,
        'message' => $value->getMessage(),
        'code' => $value->getCode(),
      ];
      continue;
    }

    if (is_scalar($value) || $value === null) {
      $out[$key] = $value;
      continue;
    }

    if (is_array($value)) {
      $out[$key] = sanitize_log_context($value);
      continue;
    }

    $out[$key] = '[' . get_debug_type($value) . ']';
  }

  return $out;
}

function log_ensure_log_directory(): void
{
  if (!defined('LOG_DIR')) {
    throw new RuntimeException('LOG_DIR не определён. Подключите config/config.php до вызова логгера.');
  }

  if (is_dir(LOG_DIR)) {
    return;
  }

  if (!mkdir(LOG_DIR, 0755, true) && !is_dir(LOG_DIR)) {
    throw new RuntimeException('Не удалось создать каталог логов: ' . LOG_DIR);
  }
}

function log_format_line(string $level, string $message, array $context): string
{
  $timestamp = date('c');
  $payload = $context === [] ? '' : ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

  return "[{$timestamp}] {$level} {$message}{$payload}" . PHP_EOL;
}

function log_write(string $level, string $message, array $context = []): void
{
  if (!should_log($level)) {
    return;
  }

  if (!defined('LOG_DIR') || !defined('LOG_FILE')) {
    throw new RuntimeException('LOG_DIR / LOG_FILE не определены. Подключите config/config.php.');
  }

  log_ensure_log_directory();

  $safeContext = sanitize_log_context($context);
  $line = log_format_line(strtoupper($level), $message, $safeContext);
  $path = LOG_DIR . DIRECTORY_SEPARATOR . LOG_FILE;

  $written = file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
  if ($written === false) {
    error_log('Logger: не удалось записать в файл: ' . $path);
  }
}

function log_debug(string $message, array $context = []): void
{
  log_write('debug', $message, $context);
}

function log_info(string $message, array $context = []): void
{
  log_write('info', $message, $context);
}

function log_warning(string $message, array $context = []): void
{
  log_write('warning', $message, $context);
}

function log_error(string $message, array $context = []): void
{
  log_write('error', $message, $context);
}

function log_exception(Throwable $e, array $context = []): void
{
  $base = [
    'exception' => $e::class,
    'message' => $e->getMessage(),
    'file' => $e->getFile(),
    'line' => $e->getLine(),
  ];

  $trace = $e->getTraceAsString();
  if ($trace !== '') {
    $base['trace'] = $trace;
  }

  $merged = array_merge($base, $context);
  log_error('Исключение: ' . $e::class, $merged);
}
