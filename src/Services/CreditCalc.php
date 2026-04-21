<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class CreditCalc
{
  /** 1 год — высокий платёж, минимальная переплата */
  public const TERM_AGGRESSIVE = 12;

  /** 3 года — баланс платежа и переплаты */
  public const TERM_OPTIMAL = 36;

  /** 5 лет — низкий платёж, высокая переплата */
  public const TERM_MINIMAL = 60;

  /**
   * Аннуитетный платёж: annualRate — годовая ставка в процентах (например 18.5).
   *
   * @return array{monthly_payment: float, total_paid: float, overpayment: float}
   */
  public function calculateAnnuity(float $total, float $annualRate, int $months): array
  {
    if ($months <= 0) {
      throw new InvalidArgumentException('Количество месяцев должно быть больше нуля.');
    }

    if ($total <= 0) {
      throw new InvalidArgumentException('Сумма кредита должна быть больше нуля.');
    }

    if ($annualRate <= 0.0) {
      $monthlyPayment = $total / $months;
      $totalPaid = $monthlyPayment * $months;

      return [
        'monthly_payment' => round($monthlyPayment, 2),
        'total_paid' => round($totalPaid, 2),
        'overpayment' => 0.0,
      ];
    }

    $monthlyRate = ($annualRate / 12.0) / 100.0;
    $factor = pow(1.0 + $monthlyRate, $months);
    $monthlyPayment = $total * ($monthlyRate * $factor) / ($factor - 1.0);
    $totalPaid = $monthlyPayment * $months;
    $overpayment = $totalPaid - $total;

    return [
      'monthly_payment' => round($monthlyPayment, 2),
      'total_paid' => round($totalPaid, 2),
      'overpayment' => round($overpayment, 2),
    ];
  }
}
