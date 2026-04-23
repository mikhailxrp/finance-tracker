<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Category
{
  private const INCOME_TYPE = 'income';
  private const EXPENSE_TYPE = 'expense';
  private const SAVINGS_NAME = 'Накопления';
  private const DEFAULT_COLOR = '#6B7280';

  /**
   * Возвращает категории пользователя + системные категории.
   *
   * @return array<int, array{id:int,name:string,type:string,icon:string,color:string}>
   */
  public function getForUser(PDO $pdo, int $userId): array
  {
    $all = $this->getAllForUser($pdo, $userId);

    return array_values(
      array_merge(
        $all[self::INCOME_TYPE]['system'],
        $all[self::INCOME_TYPE]['custom'],
        $all[self::EXPENSE_TYPE]['system'],
        $all[self::EXPENSE_TYPE]['custom']
      )
    );
  }

  /**
   * @return array{
   *   income: array{system: array<int, array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}>, custom: array<int, array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}>},
   *   expense: array{system: array<int, array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}>, custom: array<int, array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}>}
   * }
   */
  public function getAllForUser(PDO $pdo, int $userId): array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, name, type, icon, color
       FROM categories
       WHERE user_id = :user_id OR user_id IS NULL
       ORDER BY type ASC, user_id IS NULL DESC, name ASC, id ASC'
    );
    $stmt->execute(['user_id' => $userId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
      return $this->emptyGroupedResult();
    }

    $grouped = $this->emptyGroupedResult();
    $seen = [];

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $normalized = $this->normalizeCategoryRow($row);
      if ($normalized === null) {
        continue;
      }

      $dedupeKey = mb_strtolower($normalized['type'] . '|' . $normalized['name']);
      if (isset($seen[$dedupeKey])) {
        continue;
      }
      $seen[$dedupeKey] = true;

      $targetBucket = $normalized['is_system'] ? 'system' : 'custom';
      $grouped[$normalized['type']][$targetBucket][] = $normalized;
    }

    return $grouped;
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

    $all = $this->getAllForUser($pdo, $userId);

    return array_values(array_merge(
      $all[$normalizedType]['system'],
      $all[$normalizedType]['custom']
    ));
  }

  /**
   * @return array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}|null
   */
  public function findByIdForUser(PDO $pdo, int $categoryId, int $userId): ?array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, name, type, icon, color
       FROM categories
       WHERE id = :id
         AND (user_id = :user_id OR user_id IS NULL)
       LIMIT 1'
    );
    $stmt->execute([
      'id' => $categoryId,
      'user_id' => $userId,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return null;
    }

    return $this->normalizeCategoryRow($row);
  }

  public function create(PDO $pdo, int $userId, string $name, string $type, ?string $icon, string $color): int
  {
    $normalizedType = $this->normalizeType($type);
    if ($normalizedType === null) {
      return 0;
    }

    $stmt = $pdo->prepare(
      'INSERT INTO categories (user_id, name, type, icon, color)
       VALUES (:user_id, :name, :type, :icon, :color)'
    );
    $stmt->execute([
      'user_id' => $userId,
      'name' => $name,
      'type' => $normalizedType,
      'icon' => $icon !== null && trim($icon) !== '' ? trim($icon) : null,
      'color' => $this->normalizeColor($color),
    ]);

    $lastId = (int) $pdo->lastInsertId();
    return $lastId > 0 ? $lastId : 0;
  }

  public function update(PDO $pdo, int $categoryId, int $userId, string $name, ?string $icon, string $color): bool
  {
    $stmt = $pdo->prepare(
      'UPDATE categories
       SET name = :name, icon = :icon, color = :color
       WHERE id = :id
         AND user_id = :user_id
       LIMIT 1'
    );
    $stmt->execute([
      'id' => $categoryId,
      'user_id' => $userId,
      'name' => $name,
      'icon' => $icon !== null && trim($icon) !== '' ? trim($icon) : null,
      'color' => $this->normalizeColor($color),
    ]);

    return $stmt->rowCount() > 0;
  }

  public function delete(PDO $pdo, int $categoryId, int $userId): bool
  {
    $stmt = $pdo->prepare(
      'DELETE FROM categories
       WHERE id = :id
         AND user_id = :user_id
       LIMIT 1'
    );
    $stmt->execute([
      'id' => $categoryId,
      'user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
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

  /**
   * @param array<string, mixed> $row
   * @return array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}|null
   */
  private function normalizeCategoryRow(array $row): ?array
  {
    $id = isset($row['id']) ? (int) $row['id'] : 0;
    if ($id <= 0) {
      return null;
    }

    $type = $this->normalizeType(trim((string) ($row['type'] ?? '')));
    if ($type === null) {
      return null;
    }

    $name = trim((string) ($row['name'] ?? ''));
    if ($name === '') {
      return null;
    }

    $userIdValue = $row['user_id'] ?? null;
    $isSystem = $userIdValue === null || $userIdValue === '';

    return [
      'id' => $id,
      'name' => $name,
      'type' => $type,
      'icon' => trim((string) ($row['icon'] ?? '')),
      'color' => $this->normalizeColor((string) ($row['color'] ?? self::DEFAULT_COLOR)),
      'is_system' => $isSystem,
    ];
  }

  /**
   * @return array{
   *   income: array{system: array<int, array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}>, custom: array<int, array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}>},
   *   expense: array{system: array<int, array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}>, custom: array<int, array{id:int,name:string,type:string,icon:string,color:string,is_system:bool}>}
   * }
   */
  private function emptyGroupedResult(): array
  {
    return [
      self::INCOME_TYPE => ['system' => [], 'custom' => []],
      self::EXPENSE_TYPE => ['system' => [], 'custom' => []],
    ];
  }

  private function normalizeColor(string $color): string
  {
    $trimmed = trim($color);
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $trimmed)) {
      return self::DEFAULT_COLOR;
    }

    return strtoupper($trimmed);
  }
}
