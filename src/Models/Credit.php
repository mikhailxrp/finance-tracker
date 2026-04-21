<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Credit
{
  public const STATUS_ACTIVE = 'active';
  public const STATUS_CLOSED = 'closed';

  /**
   * @return array<int, array<string, mixed>>
   */
  public function getAllForUser(PDO $pdo, int $userId): array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, title, total_amount, interest_rate, start_date, status, created_at
       FROM credits
       WHERE user_id = :user_id
       ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return is_array($rows) ? $rows : [];
  }

  /**
   * @param array{
   *   user_id:int,
   *   title:string,
   *   total_amount:string,
   *   interest_rate:string,
   *   start_date:string
   * } $data
   */
  public function create(PDO $pdo, array $data): int
  {
    $stmt = $pdo->prepare(
      'INSERT INTO credits (user_id, title, total_amount, interest_rate, start_date, status)
       VALUES (:user_id, :title, :total_amount, :interest_rate, :start_date, :status)'
    );

    $stmt->execute([
      'user_id' => $data['user_id'],
      'title' => $data['title'],
      'total_amount' => $data['total_amount'],
      'interest_rate' => $data['interest_rate'],
      'start_date' => $data['start_date'],
      'status' => self::STATUS_ACTIVE,
    ]);

    return (int) $pdo->lastInsertId();
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findForUser(PDO $pdo, int $creditId, int $userId): ?array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, title, total_amount, interest_rate, start_date, status, created_at
       FROM credits
       WHERE id = :credit_id AND user_id = :user_id
       LIMIT 1'
    );
    $stmt->execute([
      'credit_id' => $creditId,
      'user_id' => $userId,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
  }

  /**
   * @param array{
   *   title:string,
   *   total_amount:string,
   *   interest_rate:string,
   *   start_date:string
   * } $data
   */
  public function update(PDO $pdo, int $creditId, int $userId, array $data): bool
  {
    $stmt = $pdo->prepare(
      'UPDATE credits
       SET title = :title,
           total_amount = :total_amount,
           interest_rate = :interest_rate,
           start_date = :start_date
       WHERE id = :credit_id AND user_id = :user_id'
    );

    $stmt->execute([
      'credit_id' => $creditId,
      'user_id' => $userId,
      'title' => $data['title'],
      'total_amount' => $data['total_amount'],
      'interest_rate' => $data['interest_rate'],
      'start_date' => $data['start_date'],
    ]);

    return $stmt->rowCount() > 0;
  }

  public function delete(PDO $pdo, int $creditId, int $userId): bool
  {
    $stmt = $pdo->prepare('DELETE FROM credits WHERE id = :credit_id AND user_id = :user_id');
    $stmt->execute([
      'credit_id' => $creditId,
      'user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
  }

  public function setStatus(PDO $pdo, int $creditId, int $userId, string $status): bool
  {
    $stmt = $pdo->prepare(
      'UPDATE credits
       SET status = :status
       WHERE id = :credit_id AND user_id = :user_id'
    );
    $stmt->execute([
      'status' => $status,
      'credit_id' => $creditId,
      'user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
  }
}
