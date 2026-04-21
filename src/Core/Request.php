<?php

declare(strict_types=1);

/**
 * HTTP-метод текущего запроса.
 */
function requestMethod(): string
{
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  return strtoupper((string) $method);
}

/**
 * Нормализованный путь без query-параметров.
 * Примеры:
 * - /dashboard?period=month -> /dashboard
 * - dashboard -> /dashboard
 */
function requestPath(): string
{
  $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
  $path = (string) parse_url($uri, PHP_URL_PATH);
  $path = rawurldecode($path);

  if ($path === '') {
    return '/';
  }

  $normalized = '/' . trim($path, '/');
  return $normalized === '/' ? '/' : rtrim($normalized, '/');
}

/**
 * Значение из query-строки.
 */
function query(string $key, ?string $default = null): ?string
{
  $value = $_GET[$key] ?? $default;
  return is_string($value) ? $value : $default;
}

/**
 * Значение из POST-данных.
 */
function input(string $key, ?string $default = null): ?string
{
  $value = $_POST[$key] ?? $default;
  return is_string($value) ? trim($value) : $default;
}

/**
 * Все входные данные GET + POST.
 */
function allInput(): array
{
  return array_merge($_GET, $_POST);
}
