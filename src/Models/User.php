<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
  /**
   * @return array<string, string|int>|null
   */
  public function findById(PDO $pdo, int $userId): ?array
  {
    $stmt = $pdo->prepare(
      'SELECT id, name, email, password_hash, created_at
       FROM users
       WHERE id = :id
       LIMIT 1'
    );
    $stmt->execute(['id' => $userId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return null;
    }

    $id = isset($row['id']) ? (int) $row['id'] : 0;
    if ($id <= 0) {
      return null;
    }

    return [
      'id' => $id,
      'name' => trim((string) ($row['name'] ?? '')),
      'email' => trim((string) ($row['email'] ?? '')),
      'password_hash' => (string) ($row['password_hash'] ?? ''),
      'created_at' => (string) ($row['created_at'] ?? ''),
    ];
  }

  public function updateName(PDO $pdo, int $userId, string $name): bool
  {
    $stmt = $pdo->prepare('UPDATE users SET name = :name WHERE id = :id LIMIT 1');
    $stmt->execute([
      'id' => $userId,
      'name' => $name,
    ]);

    return true;
  }

  public function changePassword(PDO $pdo, int $userId, string $newHash): bool
  {
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id LIMIT 1');
    $stmt->execute([
      'id' => $userId,
      'password_hash' => $newHash,
    ]);

    return true;
  }

  /**
   * @return array{transactions:int,active_goals:int,ai_strategies:int}
   */
  public function getStats(PDO $pdo, int $userId): array
  {
    $transactionsStmt = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = :user_id');
    $transactionsStmt->execute(['user_id' => $userId]);
    $transactionsCount = (int) $transactionsStmt->fetchColumn();

    $goalsStmt = $pdo->prepare(
      'SELECT COUNT(*) FROM goals WHERE user_id = :user_id AND status = :status'
    );
    $goalsStmt->execute([
      'user_id' => $userId,
      'status' => 'active',
    ]);
    $activeGoalsCount = (int) $goalsStmt->fetchColumn();

    $aiStmt = $pdo->prepare('SELECT COUNT(*) FROM ai_strategies WHERE user_id = :user_id');
    $aiStmt->execute(['user_id' => $userId]);
    $aiStrategiesCount = (int) $aiStmt->fetchColumn();

    return [
      'transactions' => max(0, $transactionsCount),
      'active_goals' => max(0, $activeGoalsCount),
      'ai_strategies' => max(0, $aiStrategiesCount),
    ];
  }
}
