<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Goal
{
  public const STATUS_ACTIVE = 'active';
  public const STATUS_COMPLETED = 'completed';
  public const STATUS_CANCELLED = 'cancelled';

  /**
   * @return array<int, array<string, mixed>>
   */
  public function getAllForUser(PDO $pdo, int $userId): array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, title, target_amount, current_amount, period, deadline, status, created_at
       FROM goals
       WHERE user_id = :user_id
       ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return is_array($rows) ? $rows : [];
  }

  /**
   * @param array{user_id:int,title:string,target_amount:string,period:string,deadline:string} $data
   */
  public function create(PDO $pdo, array $data): int
  {
    $stmt = $pdo->prepare(
      'INSERT INTO goals (user_id, title, target_amount, current_amount, period, deadline, status)
       VALUES (:user_id, :title, :target_amount, 0, :period, :deadline, :status)'
    );

    $stmt->execute([
      'user_id' => $data['user_id'],
      'title' => $data['title'],
      'target_amount' => $data['target_amount'],
      'period' => $data['period'],
      'deadline' => $data['deadline'],
      'status' => self::STATUS_ACTIVE,
    ]);

    return (int) $pdo->lastInsertId();
  }

  /**
   * Цель из плана покупки: период «месяц», дедлайн — сегодня + term_months.
   *
   * @param array{user_id:int, title:string, target_amount:string, term_months:int} $data
   */
  public function createFromPlan(PDO $pdo, array $data): int
  {
    $termMonths = (int) ($data['term_months'] ?? 0);
    if ($termMonths <= 0) {
      throw new \InvalidArgumentException('term_months must be positive.');
    }

    $deadline = (new \DateTimeImmutable('today'))
      ->modify('+' . $termMonths . ' months')
      ->format('Y-m-d');

    return $this->create($pdo, [
      'user_id' => (int) $data['user_id'],
      'title' => $data['title'],
      'target_amount' => $data['target_amount'],
      'period' => 'month',
      'deadline' => $deadline,
    ]);
  }

  /**
   * @return array<string, mixed>|null
   */
  public function findForUser(PDO $pdo, int $goalId, int $userId): ?array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, title, target_amount, current_amount, period, deadline, status, created_at
       FROM goals
       WHERE id = :goal_id AND user_id = :user_id
       LIMIT 1'
    );
    $stmt->execute([
      'goal_id' => $goalId,
      'user_id' => $userId,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
  }

  /**
   * @param array{title:string,target_amount:string,period:string,deadline:string} $data
   */
  public function update(PDO $pdo, int $goalId, int $userId, array $data): bool
  {
    $stmt = $pdo->prepare(
      'UPDATE goals
       SET title = :title,
           target_amount = :target_amount,
           period = :period,
           deadline = :deadline
       WHERE id = :goal_id AND user_id = :user_id'
    );

    $stmt->execute([
      'goal_id' => $goalId,
      'user_id' => $userId,
      'title' => $data['title'],
      'target_amount' => $data['target_amount'],
      'period' => $data['period'],
      'deadline' => $data['deadline'],
    ]);

    return $stmt->rowCount() > 0;
  }

  public function removeContributions(PDO $pdo, int $goalId, int $userId): int
  {
    $stmt = $pdo->prepare(
      'DELETE FROM transactions WHERE goal_id = :goal_id AND user_id = :user_id'
    );
    $stmt->execute([
      'goal_id' => $goalId,
      'user_id' => $userId,
    ]);

    return $stmt->rowCount();
  }

  public function delete(PDO $pdo, int $goalId, int $userId, bool $returnFunds = false): bool
  {
    if ($returnFunds) {
      $pdo->beginTransaction();

      try {
        $this->removeContributions($pdo, $goalId, $userId);
        $stmt = $pdo->prepare('DELETE FROM goals WHERE id = :goal_id AND user_id = :user_id');
        $stmt->execute([
          'goal_id' => $goalId,
          'user_id' => $userId,
        ]);
        $deleted = $stmt->rowCount() > 0;
        $pdo->commit();

        return $deleted;
      } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        throw $e;
      }
    }

    $stmt = $pdo->prepare('DELETE FROM goals WHERE id = :goal_id AND user_id = :user_id');
    $stmt->execute([
      'goal_id' => $goalId,
      'user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
  }

  public function setStatus(
    PDO $pdo,
    int $goalId,
    int $userId,
    string $status,
    bool $returnFunds = false
  ): bool {
    if ($status === self::STATUS_ACTIVE) {
      $stmt = $pdo->prepare(
        'UPDATE goals
         SET status = :status
         WHERE id = :goal_id AND user_id = :user_id'
      );
      $stmt->execute([
        'status' => $status,
        'goal_id' => $goalId,
        'user_id' => $userId,
      ]);

      return $stmt->rowCount() > 0;
    }

    if ($status === self::STATUS_CANCELLED && $returnFunds) {
      $pdo->beginTransaction();

      try {
        $this->removeContributions($pdo, $goalId, $userId);
        $stmt = $pdo->prepare(
          'UPDATE goals
           SET current_amount = 0,
               status = :status
           WHERE id = :goal_id AND user_id = :user_id'
        );
        $stmt->execute([
          'status' => self::STATUS_CANCELLED,
          'goal_id' => $goalId,
          'user_id' => $userId,
        ]);
        $pdo->commit();

        return true;
      } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        throw $e;
      }
    }

    $stmt = $pdo->prepare(
      'UPDATE goals
       SET status = :status
       WHERE id = :goal_id AND user_id = :user_id'
    );
    $stmt->execute([
      'status' => $status,
      'goal_id' => $goalId,
      'user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
  }

  public function addContribution(
    PDO $pdo,
    int $goalId,
    int $userId,
    float $amount,
    int $savingsCategoryId,
    string $title
  ): void {
    $pdo->beginTransaction();

    try {
      $insert = $pdo->prepare(
        'INSERT INTO transactions (user_id, category_id, goal_id, amount, date, comment)
         VALUES (:user_id, :category_id, :goal_id, :amount, CURDATE(), :comment)'
      );
      $insert->execute([
        'user_id' => $userId,
        'category_id' => $savingsCategoryId,
        'goal_id' => $goalId,
        'amount' => number_format($amount, 2, '.', ''),
        'comment' => 'Пополнение цели: ' . $title,
      ]);

      $update = $pdo->prepare(
        'UPDATE goals
         SET current_amount = current_amount + :amount
         WHERE id = :goal_id AND user_id = :user_id'
      );
      $update->execute([
        'amount' => number_format($amount, 2, '.', ''),
        'goal_id' => $goalId,
        'user_id' => $userId,
      ]);
      if ($update->rowCount() === 0) {
        throw new \RuntimeException('Goal not found for contribution.');
      }

      $complete = $pdo->prepare(
        'UPDATE goals
         SET status = :status
         WHERE id = :goal_id
           AND user_id = :user_id
           AND current_amount >= target_amount'
      );
      $complete->execute([
        'status' => self::STATUS_COMPLETED,
        'goal_id' => $goalId,
        'user_id' => $userId,
      ]);

      $pdo->commit();
    } catch (\Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }
  }
}
