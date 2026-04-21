<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Подключение к MySQL через PDO с параметрами из `.env`.
 */
function getPdo(): PDO
{
  static $pdo = null;
  if ($pdo instanceof PDO) {
    return $pdo;
  }

  if (!extension_loaded('pdo_mysql')) {
    throw new RuntimeException(
      'Не загружено расширение PHP pdo_mysql. Включите его в php.ini (extension=pdo_mysql) и перезапустите PHP.'
    );
  }

  $dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    env('DB_HOST'),
    env('DB_NAME'),
    env('DB_CHARSET')
  );

  $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  return $pdo;
}

/**
 * Имя и email текущего пользователя из БД (для шапки и защищённых страниц).
 */
function getAuthenticatedUserProfile(): ?array
{
  ensureSessionStarted();
  $userId = normalizeUserId($_SESSION['user_id'] ?? null);
  if ($userId === null) {
    return null;
  }

  try {
    $pdo = getPdo();
    $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return null;
    }

    return [
      'name' => (string) ($row['name'] ?? ''),
      'email' => (string) ($row['email'] ?? ''),
    ];
  } catch (Throwable) {
    return null;
  }
}
