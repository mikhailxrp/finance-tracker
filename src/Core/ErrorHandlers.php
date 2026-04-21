<?php

declare(strict_types=1);

/**
 * Глобальные обработчики ошибок и исключений.
 *
 * Подключать после `config/config.php` (нужны `APP_ENV`, функции логгера).
 *
 * Политика ответа пользователю:
 * - `APP_ENV === 'local'` — краткие детали (сообщение; для исключений — файл и строка).
 * - иначе — только обобщённый текст без путей и трейсов.
 */

/**
 * Регистрирует обработчики: необработанные исключения, ошибки уровня E_* (лог), shutdown для фаталов.
 */
function register_app_error_handlers(): void
{
  set_exception_handler(static function (Throwable $e): void {
    app_handle_uncaught_exception($e);
  });

  set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline): bool {
    return app_handle_php_error($errno, $errstr, $errfile, $errline);
  });

  register_shutdown_function(static function (): void {
    app_handle_shutdown_fatal();
  });
}

function app_show_error_details_to_user(): bool
{
  return defined('APP_ENV') && APP_ENV === 'local';
}

function app_handle_uncaught_exception(Throwable $e): void
{
  $GLOBALS['__app_uncaught_exception_handled'] = true;

  log_exception($e, ['handler' => 'uncaught_exception']);

  if (!headers_sent()) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
  }

  echo app_format_exception_http_body($e);
}

/**
 * @param array{type: int, message: string, file: string, line: int} $error
 */
function app_format_fatal_http_body(array $error): string
{
  if (app_show_error_details_to_user()) {
    return sprintf(
      "Фатальная ошибка\n%s\n%s:%d",
      $error['message'],
      $error['file'],
      $error['line']
    );
  }

  return '500 Internal Server Error';
}

function app_format_exception_http_body(Throwable $e): string
{
  if (app_show_error_details_to_user()) {
    return sprintf(
      "%s\n%s:%d",
      $e->getMessage(),
      $e->getFile(),
      $e->getLine()
    );
  }

  return '500 Internal Server Error';
}

function app_handle_shutdown_fatal(): void
{
  if (!empty($GLOBALS['__app_uncaught_exception_handled'])) {
    return;
  }

  $error = error_get_last();
  if ($error === null) {
    return;
  }

  $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
  if (!in_array($error['type'], $fatalTypes, true)) {
    return;
  }

  log_error('Фатальная ошибка PHP (shutdown)', [
    'type' => $error['type'],
    'message' => $error['message'],
    'file' => $error['file'],
    'line' => $error['line'],
  ]);

  if (!headers_sent()) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
  }

  echo app_format_fatal_http_body($error);
}

/**
 * Логирует предупреждения/нотисы PHP; возвращает false, чтобы сработал встроенный обработчик (если включён).
 */
function app_handle_php_error(int $errno, string $errstr, string $errfile, int $errline): bool
{
  if ((error_reporting() & $errno) === 0) {
    return false;
  }

  $severity = match ($errno) {
    E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING => 'warning',
    E_NOTICE, E_USER_NOTICE, E_STRICT, E_DEPRECATED, E_USER_DEPRECATED => 'notice',
    default => 'error',
  };

  $payload = [
    'errno' => $errno,
    'severity' => $severity,
    'message' => $errstr,
    'file' => $errfile,
    'line' => $errline,
  ];

  if ($severity === 'notice') {
    log_debug('PHP notice', $payload);
  } else {
    log_error('PHP runtime error', $payload);
  }

  return false;
}
