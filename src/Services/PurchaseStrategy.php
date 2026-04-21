<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

final class PurchaseStrategy
{
  public const MULTIPLIER_FAST = 0.5;

  public const MULTIPLIER_MODERATE = 1.0;

  public const MULTIPLIER_CAREFUL = 1.5;

  /** @var array<int, int> */
  public const AVAILABLE_TERMS = [3, 6, 9, 12, 18, 24, 36];

  public const MIN_TERM_MONTHS = 1;

  /**
   * @return array{
   *   strategies: array<int, array{
   *     label: string,
   *     emoji: string,
   *     monthly_amount: float,
   *     months: int,
   *     target_date: string
   *   }>
   * }
   */
  public function calculate(float $targetAmount, int $userMonths): array
  {
    if ($targetAmount <= 0) {
      throw new InvalidArgumentException('Сумма цели покупки должна быть больше нуля.');
    }

    if ($userMonths < self::MIN_TERM_MONTHS) {
      throw new InvalidArgumentException('Срок накопления должен быть не менее одного месяца.');
    }

    $today = new DateTimeImmutable('today');
    $defs = [
      ['label' => 'Быстро', 'emoji' => '🚀', 'multiplier' => self::MULTIPLIER_FAST],
      ['label' => 'Умеренно', 'emoji' => '⚖️', 'multiplier' => self::MULTIPLIER_MODERATE],
      ['label' => 'Разумно', 'emoji' => '🌿', 'multiplier' => self::MULTIPLIER_CAREFUL],
    ];

    $strategies = [];
    foreach ($defs as $def) {
      $multiplier = (float) $def['multiplier'];
      $rawMonths = $userMonths * $multiplier;
      $scenarioMonths = max(self::MIN_TERM_MONTHS, (int) ceil($rawMonths));
      $monthlyAmount = $targetAmount / $scenarioMonths;
      $targetDate = $today->modify('+' . $scenarioMonths . ' months');

      $strategies[] = [
        'label' => (string) $def['label'],
        'emoji' => (string) $def['emoji'],
        'monthly_amount' => round($monthlyAmount, 2),
        'months' => $scenarioMonths,
        'target_date' => $targetDate->format('Y-m-d'),
      ];
    }

    return ['strategies' => $strategies];
  }
}
