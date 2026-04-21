<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class PurchasePlan
{
  /**
   * @return array<int, array<string, mixed>>
   */
  public function getAllForUser(PDO $pdo, int $userId): array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, title, target_amount, term_months, created_at
       FROM purchase_plans
       WHERE user_id = :user_id
       ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return is_array($rows) ? $rows : [];
  }

  /**
   * @param array{user_id:int, title:string, target_amount:string, term_months:int} $data
   */
  public function create(PDO $pdo, array $data): int
  {
    $stmt = $pdo->prepare(
      'INSERT INTO purchase_plans (user_id, title, target_amount, term_months)
       VALUES (:user_id, :title, :target_amount, :term_months)'
    );
    $stmt->execute([
      'user_id' => $data['user_id'],
      'title' => $data['title'],
      'target_amount' => $data['target_amount'],
      'term_months' => $data['term_months'],
    ]);

    return (int) $pdo->lastInsertId();
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findForUser(PDO $pdo, int $planId, int $userId): ?array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, title, target_amount, term_months, created_at
       FROM purchase_plans
       WHERE id = :plan_id AND user_id = :user_id
       LIMIT 1'
    );
    $stmt->execute([
      'plan_id' => $planId,
      'user_id' => $userId,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
  }

  public function delete(PDO $pdo, int $planId, int $userId): bool
  {
    $stmt = $pdo->prepare(
      'DELETE FROM purchase_plans WHERE id = :plan_id AND user_id = :user_id'
    );
    $stmt->execute([
      'plan_id' => $planId,
      'user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
  }
}
