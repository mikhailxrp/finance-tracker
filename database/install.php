<?php

declare(strict_types=1);

/**
 * Однократная установка схемы БД по `.docs/database.md`.
 * Запуск из корня проекта: php database/install.php
 *
 * Если CLI пишет «could not find driver» и `php --ini` показывает (none),
 * включите pdo_mysql в php.ini или однократно:
 * php -d extension_dir="C:\path\to\php\ext" -d extension=pdo_mysql database/install.php
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/src/Core/Database.php';

$pdo = getPdo();

/**
 * Проверка наличия колонки (для идемпотентной миграции).
 */
function columnExists(PDO $pdo, string $table, string $column): bool
{
  $stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
  );
  $stmt->execute([$table, $column]);
  return (int) $stmt->fetchColumn() > 0;
}

$ddl = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(100) NOT NULL,
  email             VARCHAR(150) NOT NULL UNIQUE,
  password_hash     VARCHAR(255) NOT NULL,
  email_verified_at TIMESTAMP NULL DEFAULT NULL,
  email_token       VARCHAR(64) NULL DEFAULT NULL,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name    VARCHAR(100) NOT NULL,
  type    ENUM('income','expense') NOT NULL,
  icon    VARCHAR(50),
  color   VARCHAR(7) DEFAULT '#6B7280',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  category_id INT NOT NULL,
  amount      DECIMAL(10,2) NOT NULL,
  date        DATE NOT NULL,
  comment     VARCHAR(255),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goals (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT NOT NULL,
  title          VARCHAR(150) NOT NULL,
  target_amount  DECIMAL(10,2) NOT NULL,
  current_amount DECIMAL(10,2) DEFAULT 0,
  period         ENUM('week','month','year') NOT NULL,
  deadline       DATE,
  status         ENUM('active','completed','cancelled') DEFAULT 'active',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_plans (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  title         VARCHAR(150) NOT NULL,
  target_amount DECIMAL(10,2) NOT NULL,
  term_months   INT NOT NULL DEFAULT 12,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS credits (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  title         VARCHAR(150) NOT NULL,
  total_amount  DECIMAL(10,2) NOT NULL,
  interest_rate DECIMAL(5,2) NOT NULL,
  start_date    DATE NOT NULL,
  status        ENUM('active','closed') DEFAULT 'active',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_strategies (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  request_data JSON,
  response_text TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  token      VARCHAR(64) NOT NULL UNIQUE,
  expires_at TIMESTAMP NOT NULL,
  last_request_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_expires (expires_at),
  INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

foreach (array_filter(array_map('trim', explode(';', $ddl))) as $statement) {
  if ($statement !== '') {
    $pdo->exec($statement);
  }
}

if (!columnExists($pdo, 'users', 'email_verified_at')) {
  $pdo->exec(
    'ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER password_hash'
  );
}

if (!columnExists($pdo, 'users', 'email_token')) {
  $pdo->exec(
    'ALTER TABLE users ADD COLUMN email_token VARCHAR(64) NULL DEFAULT NULL AFTER email_verified_at'
  );
}

$pdo->exec(
  "UPDATE users SET email_verified_at = CURRENT_TIMESTAMP
   WHERE email_verified_at IS NULL AND email_token IS NULL"
);

$systemCategoriesCount = (int) $pdo
  ->query('SELECT COUNT(*) FROM categories WHERE user_id IS NULL')
  ->fetchColumn();

if ($systemCategoriesCount === 0) {
  $seed = $pdo->prepare(
    'INSERT INTO categories (user_id, name, type, icon, color) VALUES (NULL, ?, ?, ?, ?)'
  );

  $rows = [
    ['Зарплата', 'income', '💼', '#00C9A7'],
    ['Подработка', 'income', '💡', '#4F8EF7'],
    ['Продукты', 'expense', '🛒', '#F87171'],
    ['Развлечения', 'expense', '🎮', '#A78BFA'],
    ['Маркетплейсы', 'expense', '📦', '#FBBF24'],
    ['Вредные привычки', 'expense', '🚬', '#FB923C'],
    ['Домашние животные', 'expense', '🐾', '#34D399'],
  ];

  foreach ($rows as [$name, $type, $icon, $color]) {
    $seed->execute([$name, $type, $icon, $color]);
  }
}

echo "OK: схема БД установлена.\n";
