<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Category
{
  private const INCOME_TYPE = 'income';
  private const EXPENSE_TYPE = 'expense';
  private const SAVINGS_NAME = 'Накопления';

  /**
   * Возвращает категории пользователя + системные категории.
   *
   * @return array<int, array{id:int,name:string,type:string,icon:string,color:string}>
   */
  public function getForUser(PDO $pdo, int $userId): array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, name, type, icon, color
       FROM categories
       WHERE user_id = :user_id OR user_id IS NULL
       ORDER BY type ASC, name ASC, user_id IS NULL ASC, id ASC'
    );
    $stmt->execute(['user_id' => $userId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
      return [];
    }

    $result = [];
    $seen = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $id = isset($row['id']) ? (int) $row['id'] : 0;
      if ($id <= 0) {
        continue;
      }

      $name = trim((string) ($row['name'] ?? ''));
      $type = trim((string) ($row['type'] ?? ''));
      if ($name === '' || $type === '') {
        continue;
      }

      $dedupeKey = mb_strtolower($type . '|' . $name);
      if (isset($seen[$dedupeKey])) {
        continue;
      }
      $seen[$dedupeKey] = true;

      $result[] = [
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'icon' => (string) ($row['icon'] ?? ''),
        'color' => (string) ($row['color'] ?? '#6B7280'),
      ];
    }

    return $result;
  }

  /**
   * @return array<int, array{id:int,name:string,type:string,icon:string,color:string}>
   */
  public function getForUserByType(PDO $pdo, int $userId, string $type): array
  {
    $normalizedType = $this->normalizeType($type);
    if ($normalizedType === null) {
      return [];
    }

    $stmt = $pdo->prepare(
      'SELECT id, user_id, name, type, icon, color
       FROM categories
       WHERE (user_id = :user_id OR user_id IS NULL) AND type = :type
       ORDER BY name ASC, user_id IS NULL ASC, id ASC'
    );
    $stmt->execute([
      'user_id' => $userId,
      'type' => $normalizedType,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
      return [];
    }

    $result = [];
    $seen = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $id = isset($row['id']) ? (int) $row['id'] : 0;
      if ($id <= 0) {
        continue;
      }

      $name = trim((string) ($row['name'] ?? ''));
      if ($name === '') {
        continue;
      }

      $dedupeKey = mb_strtolower($normalizedType . '|' . $name);
      if (isset($seen[$dedupeKey])) {
        continue;
      }
      $seen[$dedupeKey] = true;

      $result[] = [
        'id' => $id,
        'name' => $name,
        'type' => $normalizedType,
        'icon' => (string) ($row['icon'] ?? ''),
        'color' => (string) ($row['color'] ?? '#6B7280'),
      ];
    }

    return $result;
  }

  public function getSavingsCategoryId(PDO $pdo): ?int
  {
    $stmt = $pdo->prepare(
      'SELECT id
       FROM categories
       WHERE user_id IS NULL
         AND name = :name
         AND type = :type
       ORDER BY id ASC
       LIMIT 1'
    );
    $stmt->execute([
      'name' => self::SAVINGS_NAME,
      'type' => self::EXPENSE_TYPE,
    ]);

    $value = $stmt->fetchColumn();
    if (!is_numeric($value)) {
      return null;
    }

    $id = (int) $value;
    return $id > 0 ? $id : null;
  }

  private function normalizeType(string $type): ?string
  {
    return match ($type) {
      self::INCOME_TYPE => self::INCOME_TYPE,
      self::EXPENSE_TYPE => self::EXPENSE_TYPE,
      default => null,
    };
  }
}
