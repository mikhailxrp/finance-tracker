<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class AiStrategy
{
  private const PERIOD_WEEK_DAYS = 7;
  private const PERIOD_MONTH_DAYS = 30;
  private const PERIOD_YEAR_DAYS = 365;

  /**
   * @return array<string, mixed>|null
   */
  public function findLatestForUser(PDO $pdo, int $userId): ?array
  {
    $stmt = $pdo->prepare(
      'SELECT id, user_id, request_data, response_text, created_at
       FROM ai_strategies
       WHERE user_id = :user_id
       ORDER BY created_at DESC, id DESC
       LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return null;
    }

    $requestData = [];
    $requestDataRaw = $row['request_data'] ?? null;
    if (is_string($requestDataRaw) && $requestDataRaw !== '') {
      $decoded = json_decode($requestDataRaw, true);
      if (is_array($decoded)) {
        $requestData = $decoded;
      }
    } elseif (is_array($requestDataRaw)) {
      $requestData = $requestDataRaw;
    }

    $row['request_data'] = $requestData;
    $row['response_text'] = (string) ($row['response_text'] ?? '');
    $row['created_at'] = (string) ($row['created_at'] ?? '');

    return $row;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function getAllForUser(PDO $pdo, int $userId, string $period = 'all'): array
  {
    $sql = 'SELECT id, user_id, request_data, response_text, created_at
      FROM ai_strategies
      WHERE user_id = :user_id';
    $params = ['user_id' => $userId];

    $days = $this->mapPeriodToDays($period);
    if ($days !== null) {
      $sql .= ' AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)';
      $params['days'] = $days;
    }

    $sql .= ' ORDER BY created_at DESC, id DESC';
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
      $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
      return [];
    }

    $result = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $result[] = $this->mapHistoryRow($row);
    }

    return $result;
  }

  private function mapPeriodToDays(string $period): ?int
  {
    return match ($period) {
      'week' => self::PERIOD_WEEK_DAYS,
      'month' => self::PERIOD_MONTH_DAYS,
      'year' => self::PERIOD_YEAR_DAYS,
      default => null,
    };
  }

  /**
   * @param array<string, mixed> $row
   * @return array<string, mixed>
   */
  private function mapHistoryRow(array $row): array
  {
    $requestData = [];
    $requestDataRaw = $row['request_data'] ?? null;
    if (is_string($requestDataRaw) && $requestDataRaw !== '') {
      $decoded = json_decode($requestDataRaw, true);
      if (is_array($decoded)) {
        $requestData = $decoded;
      }
    } elseif (is_array($requestDataRaw)) {
      $requestData = $requestDataRaw;
    }

    $message = isset($requestData['message']) && is_string($requestData['message'])
      ? trim($requestData['message'])
      : '';

    return [
      'id' => (int) ($row['id'] ?? 0),
      'user_id' => (int) ($row['user_id'] ?? 0),
      'created_at' => (string) ($row['created_at'] ?? ''),
      'request_data' => $requestData,
      'request_text' => $message,
      'response_text' => (string) ($row['response_text'] ?? ''),
    ];
  }
}
