<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Transaction
{
  private const INCOME_TYPE = 'income';
  private const EXPENSE_TYPE = 'expense';
  private const DEFAULT_ZERO_AMOUNT = '0.00';
  private const DEFAULT_CHART_COLOR = '#9CA3AF';
  private const PERIOD_WEEK = 'week';
  private const PERIOD_MONTH = 'month';
  private const PERIOD_YEAR = 'year';
  private const DEFAULT_PERIOD = self::PERIOD_MONTH;

  /**
   * @return array<int, array{
   *   id:int,
   *   amount:string,
   *   date:string,
   *   comment:string,
   *   category_name:string,
   *   category_type:string,
   *   category_icon:string,
   *   category_color:string
   * }>
   */
  public function getRecentForUser(PDO $pdo, int $userId, int $limit = 50, string $period = self::DEFAULT_PERIOD): array
  {
    $safeLimit = max(1, min($limit, 200));
    $dateRange = $this->resolveDateRangeForPeriod($period);
    $sql = sprintf(
      'SELECT t.id, t.amount, t.date, t.comment, c.name AS category_name, c.type AS category_type, c.icon AS category_icon, c.color AS category_color
       FROM transactions t
       INNER JOIN categories c ON c.id = t.category_id
       WHERE t.user_id = :user_id AND t.date BETWEEN :start_date AND :end_date
       ORDER BY t.date DESC, t.id DESC
       LIMIT %d',
      $safeLimit
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      'user_id' => $userId,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
      return [];
    }

    $result = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $id = isset($row['id']) ? (int) $row['id'] : 0;
      if ($id <= 0) {
        continue;
      }

      $result[] = [
        'id' => $id,
        'amount' => (string) ($row['amount'] ?? '0'),
        'date' => (string) ($row['date'] ?? ''),
        'comment' => (string) ($row['comment'] ?? ''),
        'category_name' => (string) ($row['category_name'] ?? ''),
        'category_type' => (string) ($row['category_type'] ?? ''),
        'category_icon' => (string) ($row['category_icon'] ?? ''),
        'category_color' => (string) ($row['category_color'] ?? '#6B7280'),
      ];
    }

    return $result;
  }

  /**
   * @return array<int, array{
   *   id:int,
   *   amount:string,
   *   date:string,
   *   comment:string,
   *   category_name:string,
   *   category_type:string,
   *   category_icon:string,
   *   category_color:string
   * }>
   */
  public function getRecentForUserByType(
    PDO $pdo,
    int $userId,
    string $type,
    int $limit = 50,
    string $period = self::DEFAULT_PERIOD
  ): array
  {
    $normalizedType = $this->normalizeType($type);
    if ($normalizedType === null) {
      return [];
    }

    $safeLimit = max(1, min($limit, 200));
    $dateRange = $this->resolveDateRangeForPeriod($period);
    $sql = sprintf(
      'SELECT t.id, t.amount, t.date, t.comment, c.name AS category_name, c.type AS category_type, c.icon AS category_icon, c.color AS category_color
       FROM transactions t
       INNER JOIN categories c ON c.id = t.category_id
       WHERE t.user_id = :user_id AND c.type = :type AND t.date BETWEEN :start_date AND :end_date
       ORDER BY t.date DESC, t.id DESC
       LIMIT %d',
      $safeLimit
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      'user_id' => $userId,
      'type' => $normalizedType,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows)) {
      return [];
    }

    $result = [];
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $id = isset($row['id']) ? (int) $row['id'] : 0;
      if ($id <= 0) {
        continue;
      }

      $result[] = [
        'id' => $id,
        'amount' => (string) ($row['amount'] ?? '0'),
        'date' => (string) ($row['date'] ?? ''),
        'comment' => (string) ($row['comment'] ?? ''),
        'category_name' => (string) ($row['category_name'] ?? ''),
        'category_type' => (string) ($row['category_type'] ?? ''),
        'category_icon' => (string) ($row['category_icon'] ?? ''),
        'category_color' => (string) ($row['category_color'] ?? '#6B7280'),
      ];
    }

    return $result;
  }

  public function getDashboardTotalsForUser(PDO $pdo, int $userId, string $period = self::DEFAULT_PERIOD): array
  {
    $dateRange = $this->resolveDateRangeForPeriod($period);
    $stmt = $pdo->prepare(
      'SELECT
        COALESCE(SUM(CASE WHEN c.type = :income_type THEN t.amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN c.type = :expense_type THEN t.amount ELSE 0 END), 0) AS total_expense
      FROM transactions t
      INNER JOIN categories c ON c.id = t.category_id
      WHERE t.user_id = :user_id AND t.date BETWEEN :start_date AND :end_date'
    );
    $stmt->execute([
      'income_type' => self::INCOME_TYPE,
      'expense_type' => self::EXPENSE_TYPE,
      'user_id' => $userId,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return [
        'total_income' => self::DEFAULT_ZERO_AMOUNT,
        'total_expense' => self::DEFAULT_ZERO_AMOUNT,
        'balance' => self::DEFAULT_ZERO_AMOUNT,
      ];
    }

    $income = isset($row['total_income']) ? (float) $row['total_income'] : 0.0;
    $expense = isset($row['total_expense']) ? (float) $row['total_expense'] : 0.0;
    $balance = $income - $expense;

    return [
      'total_income' => number_format($income, 2, '.', ''),
      'total_expense' => number_format($expense, 2, '.', ''),
      'balance' => number_format($balance, 2, '.', ''),
    ];
  }

  /**
   * @return array{
   *   category_name:string,
   *   amount:string
   * }|null
   */
  public function getTopExpenseCategoryForUser(PDO $pdo, int $userId, string $period = self::DEFAULT_PERIOD): ?array
  {
    $dateRange = $this->resolveDateRangeForPeriod($period);
    $stmt = $pdo->prepare(
      'SELECT
        c.name AS category_name,
        SUM(t.amount) AS amount
      FROM transactions t
      INNER JOIN categories c ON c.id = t.category_id
      WHERE t.user_id = :user_id AND c.type = :expense_type AND t.date BETWEEN :start_date AND :end_date
      GROUP BY c.id, c.name
      ORDER BY amount DESC, c.id ASC
      LIMIT 1'
    );
    $stmt->execute([
      'user_id' => $userId,
      'expense_type' => self::EXPENSE_TYPE,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return null;
    }

    $categoryName = isset($row['category_name']) ? trim((string) $row['category_name']) : '';
    if ($categoryName === '') {
      return null;
    }

    $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;

    return [
      'category_name' => $categoryName,
      'amount' => number_format($amount, 2, '.', ''),
    ];
  }

  /**
   * @return array<int, array{
   *   category_name:string,
   *   amount:string,
   *   percentage:float,
   *   category_color:string
   * }>
   */
  public function getExpensesByCategoryForPeriod(
    PDO $pdo,
    int $userId,
    int $limit = 5,
    string $period = self::DEFAULT_PERIOD
  ): array {
    $safeLimit = max(1, min($limit, 20));
    $dateRange = $this->resolveDateRangeForPeriod($period);
    $sql = sprintf(
      'SELECT
        c.name AS category_name,
        c.color AS category_color,
        SUM(t.amount) AS amount
      FROM transactions t
      INNER JOIN categories c ON c.id = t.category_id
      WHERE t.user_id = :user_id AND c.type = :expense_type AND t.date BETWEEN :start_date AND :end_date
      GROUP BY c.id, c.name, c.color
      ORDER BY amount DESC, c.id ASC
      LIMIT %d',
      $safeLimit
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      'user_id' => $userId,
      'expense_type' => self::EXPENSE_TYPE,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows) || $rows === []) {
      return [];
    }

    $normalizedRows = [];
    $totalAmount = 0.0;
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $categoryName = trim((string) ($row['category_name'] ?? ''));
      if ($categoryName === '') {
        $categoryName = 'Без категории';
      }

      $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
      if ($amount <= 0) {
        continue;
      }

      $color = trim((string) ($row['category_color'] ?? ''));
      if ($color === '') {
        $color = self::DEFAULT_CHART_COLOR;
      }

      $normalizedRows[] = [
        'category_name' => $categoryName,
        'amount' => $amount,
        'category_color' => $color,
      ];
      $totalAmount += $amount;
    }

    if ($normalizedRows === [] || $totalAmount <= 0) {
      return [];
    }

    $result = [];
    foreach ($normalizedRows as $item) {
      $amount = (float) $item['amount'];
      $result[] = [
        'category_name' => (string) $item['category_name'],
        'amount' => number_format($amount, 2, '.', ''),
        'percentage' => round(($amount / $totalAmount) * 100, 2),
        'category_color' => (string) $item['category_color'],
      ];
    }

    return $result;
  }

  /**
   * @return array<int, array{
   *   date:string,
   *   income:string,
   *   expense:string
   * }>
   */
  public function getDailyDynamicsForPeriod(PDO $pdo, int $userId, string $period = self::DEFAULT_PERIOD): array
  {
    $dateRange = $this->resolveDateRangeForPeriod($period);
    $stmt = $pdo->prepare(
      'SELECT
        t.date AS transaction_date,
        c.type AS category_type,
        SUM(t.amount) AS amount
      FROM transactions t
      INNER JOIN categories c ON c.id = t.category_id
      WHERE t.user_id = :user_id AND t.date BETWEEN :start_date AND :end_date
      GROUP BY t.date, c.type
      ORDER BY t.date ASC'
    );
    $stmt->execute([
      'user_id' => $userId,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $amountByDate = [];
    if (is_array($rows)) {
      foreach ($rows as $row) {
        if (!is_array($row)) {
          continue;
        }

        $date = (string) ($row['transaction_date'] ?? '');
        $type = (string) ($row['category_type'] ?? '');
        if ($date === '' || ($type !== self::INCOME_TYPE && $type !== self::EXPENSE_TYPE)) {
          continue;
        }

        if (!isset($amountByDate[$date])) {
          $amountByDate[$date] = [
            self::INCOME_TYPE => 0.0,
            self::EXPENSE_TYPE => 0.0,
          ];
        }

        $amountByDate[$date][$type] = isset($row['amount']) ? (float) $row['amount'] : 0.0;
      }
    }

    $rangeStart = new \DateTimeImmutable($dateRange['start_date']);
    $rangeEnd = new \DateTimeImmutable($dateRange['end_date']);
    $interval = new \DateInterval('P1D');
    $periodRange = new \DatePeriod($rangeStart, $interval, $rangeEnd->modify('+1 day'));

    $result = [];
    foreach ($periodRange as $datePoint) {
      $dateKey = $datePoint->format('Y-m-d');
      $dayData = $amountByDate[$dateKey] ?? [
        self::INCOME_TYPE => 0.0,
        self::EXPENSE_TYPE => 0.0,
      ];

      $result[] = [
        'date' => $dateKey,
        'income' => number_format((float) $dayData[self::INCOME_TYPE], 2, '.', ''),
        'expense' => number_format((float) $dayData[self::EXPENSE_TYPE], 2, '.', ''),
      ];
    }

    return $result;
  }

  /**
   * @return array{total:string, top_source_name:string, avg_per_month:string}
   */
  public function getIncomeStatsForUser(PDO $pdo, int $userId, string $period = self::DEFAULT_PERIOD): array
  {
    $dateRange = $this->resolveDateRangeForPeriod($period);

    $totalStmt = $pdo->prepare(
      'SELECT COALESCE(SUM(t.amount), 0) AS total
       FROM transactions t
       INNER JOIN categories c ON c.id = t.category_id
       WHERE t.user_id = :user_id AND c.type = :income_type AND t.date BETWEEN :start_date AND :end_date'
    );
    $totalStmt->execute([
      'user_id' => $userId,
      'income_type' => self::INCOME_TYPE,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);
    $totalRow = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $total = is_array($totalRow) && isset($totalRow['total']) ? (float) $totalRow['total'] : 0.0;

    $topSourceStmt = $pdo->prepare(
      'SELECT c.name AS top_source_name, SUM(t.amount) AS amount
       FROM transactions t
       INNER JOIN categories c ON c.id = t.category_id
       WHERE t.user_id = :user_id AND c.type = :income_type AND t.date BETWEEN :start_date AND :end_date
       GROUP BY c.id, c.name
       ORDER BY amount DESC, c.id ASC
       LIMIT 1'
    );
    $topSourceStmt->execute([
      'user_id' => $userId,
      'income_type' => self::INCOME_TYPE,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);
    $topSourceRow = $topSourceStmt->fetch(PDO::FETCH_ASSOC);
    $topSourceName = is_array($topSourceRow) ? trim((string) ($topSourceRow['top_source_name'] ?? '')) : '';

    $monthCount = $this->resolveMonthCountForPeriod($period);
    $avgPerMonth = $monthCount > 0 ? round($total / $monthCount, 0) : 0.0;

    return [
      'total' => number_format($total, 2, '.', ''),
      'top_source_name' => $topSourceName,
      'avg_per_month' => number_format($avgPerMonth, 0, '.', ''),
    ];
  }

  /**
   * @return array{total:string, top_category_name:string, avg_per_month:string}
   */
  public function getExpenseStatsForUser(PDO $pdo, int $userId, string $period = self::DEFAULT_PERIOD): array
  {
    $dateRange = $this->resolveDateRangeForPeriod($period);

    $totalStmt = $pdo->prepare(
      'SELECT COALESCE(SUM(t.amount), 0) AS total
       FROM transactions t
       INNER JOIN categories c ON c.id = t.category_id
       WHERE t.user_id = :user_id AND c.type = :expense_type AND t.date BETWEEN :start_date AND :end_date'
    );
    $totalStmt->execute([
      'user_id' => $userId,
      'expense_type' => self::EXPENSE_TYPE,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);
    $totalRow = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $total = is_array($totalRow) && isset($totalRow['total']) ? (float) $totalRow['total'] : 0.0;

    $topCategoryStmt = $pdo->prepare(
      'SELECT c.name AS top_category_name, SUM(t.amount) AS amount
       FROM transactions t
       INNER JOIN categories c ON c.id = t.category_id
       WHERE t.user_id = :user_id AND c.type = :expense_type AND t.date BETWEEN :start_date AND :end_date
       GROUP BY c.id, c.name
       ORDER BY amount DESC, c.id ASC
       LIMIT 1'
    );
    $topCategoryStmt->execute([
      'user_id' => $userId,
      'expense_type' => self::EXPENSE_TYPE,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);
    $topCategoryRow = $topCategoryStmt->fetch(PDO::FETCH_ASSOC);
    $topCategoryName = is_array($topCategoryRow) ? trim((string) ($topCategoryRow['top_category_name'] ?? '')) : '';

    $monthCount = $this->resolveMonthCountForPeriod($period);
    $avgPerMonth = $monthCount > 0 ? round($total / $monthCount, 0) : 0.0;

    return [
      'total' => number_format($total, 2, '.', ''),
      'top_category_name' => $topCategoryName,
      'avg_per_month' => number_format($avgPerMonth, 0, '.', ''),
    ];
  }

  /**
   * @return array{
   *   categories:array<int, string>,
   *   months:array<int, string>,
   *   series:array<int, array{name:string, data:array<int, float>}>
   * }
   */
  public function getIncomeByCategoryMonthlyForUser(PDO $pdo, int $userId, string $period = self::DEFAULT_PERIOD): array
  {
    $dateRange = $this->resolveDateRangeForPeriod($period);
    $stmt = $pdo->prepare(
      "SELECT
        c.name AS category_name,
        DATE_FORMAT(t.date, '%Y-%m') AS month_key,
        SUM(t.amount) AS amount
      FROM transactions t
      INNER JOIN categories c ON c.id = t.category_id
      WHERE t.user_id = :user_id AND c.type = :income_type AND t.date BETWEEN :start_date AND :end_date
      GROUP BY c.id, c.name, month_key
      ORDER BY month_key ASC, c.name ASC"
    );
    $stmt->execute([
      'user_id' => $userId,
      'income_type' => self::INCOME_TYPE,
      'start_date' => $dateRange['start_date'],
      'end_date' => $dateRange['end_date'],
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!is_array($rows) || $rows === []) {
      return [
        'categories' => [],
        'months' => [],
        'series' => [],
      ];
    }

    $monthsMap = [];
    $categoriesMap = [];
    $amounts = [];

    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $categoryName = trim((string) ($row['category_name'] ?? ''));
      $monthKey = (string) ($row['month_key'] ?? '');
      if ($categoryName === '' || $monthKey === '') {
        continue;
      }

      $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
      if ($amount <= 0) {
        continue;
      }

      $monthsMap[$monthKey] = true;
      $categoriesMap[$categoryName] = true;
      if (!isset($amounts[$categoryName])) {
        $amounts[$categoryName] = [];
      }
      $amounts[$categoryName][$monthKey] = $amount;
    }

    $months = array_keys($monthsMap);
    sort($months);
    $categories = array_keys($categoriesMap);
    sort($categories);

    $series = [];
    foreach ($categories as $categoryName) {
      $data = [];
      foreach ($months as $monthKey) {
        $data[] = (float) ($amounts[$categoryName][$monthKey] ?? 0.0);
      }

      $series[] = [
        'name' => $categoryName,
        'data' => $data,
      ];
    }

    return [
      'categories' => $categories,
      'months' => $months,
      'series' => $series,
    ];
  }

  /**
   * @return array<string, array<int, array{
   *   id:int,
   *   amount:string,
   *   date:string,
   *   comment:string,
   *   category_name:string,
   *   category_type:string,
   *   category_icon:string,
   *   category_color:string
   * }>>
   */
  public function getIncomeGroupedByMonthForUser(PDO $pdo, int $userId, string $period = self::DEFAULT_PERIOD): array
  {
    $transactions = $this->getRecentForUserByType($pdo, $userId, self::INCOME_TYPE, 200, $period);
    if ($transactions === []) {
      return [];
    }

    $grouped = [];
    foreach ($transactions as $transaction) {
      if (!is_array($transaction)) {
        continue;
      }

      $dateValue = (string) ($transaction['date'] ?? '');
      if ($dateValue === '') {
        continue;
      }

      $monthKey = substr($dateValue, 0, 7);
      if ($monthKey === '' || strlen($monthKey) !== 7) {
        continue;
      }

      if (!isset($grouped[$monthKey])) {
        $grouped[$monthKey] = [];
      }
      $grouped[$monthKey][] = $transaction;
    }

    return $grouped;
  }

  /**
   * @return array<string, array<int, array{
   *   id:int,
   *   amount:string,
   *   date:string,
   *   comment:string,
   *   category_name:string,
   *   category_type:string,
   *   category_icon:string,
   *   category_color:string
   * }>>
   */
  public function getExpensesGroupedByMonthForUser(PDO $pdo, int $userId, string $period = self::DEFAULT_PERIOD): array
  {
    $transactions = $this->getRecentForUserByType($pdo, $userId, self::EXPENSE_TYPE, 200, $period);
    if ($transactions === []) {
      return [];
    }

    $grouped = [];
    foreach ($transactions as $transaction) {
      if (!is_array($transaction)) {
        continue;
      }

      $dateValue = (string) ($transaction['date'] ?? '');
      if ($dateValue === '') {
        continue;
      }

      $monthKey = substr($dateValue, 0, 7);
      if ($monthKey === '' || strlen($monthKey) !== 7) {
        continue;
      }

      if (!isset($grouped[$monthKey])) {
        $grouped[$monthKey] = [];
      }
      $grouped[$monthKey][] = $transaction;
    }

    return $grouped;
  }

  private function normalizeType(string $type): ?string
  {
    return match ($type) {
      self::INCOME_TYPE => self::INCOME_TYPE,
      self::EXPENSE_TYPE => self::EXPENSE_TYPE,
      default => null,
    };
  }

  public function normalizePeriod(string $period): string
  {
    return match ($period) {
      self::PERIOD_WEEK => self::PERIOD_WEEK,
      self::PERIOD_MONTH => self::PERIOD_MONTH,
      self::PERIOD_YEAR => self::PERIOD_YEAR,
      default => self::DEFAULT_PERIOD,
    };
  }

  /**
   * Средний месячный доход за скользящее окно: сумма / число календарных месяцев,
   * в которых есть хотя бы одна операция дохода. Нет доходов в окне — null.
   */
  public function getAverageMonthlyIncome(PDO $pdo, int $userId, int $months): ?float
  {
    return $this->getAverageMonthlyByType($pdo, $userId, $months, self::INCOME_TYPE);
  }

  /**
   * Средний месячный расход за скользящее окно. Нет расходов в окне — 0.0.
   */
  public function getAverageMonthlyExpense(PDO $pdo, int $userId, int $months): ?float
  {
    $avg = $this->getAverageMonthlyByType($pdo, $userId, $months, self::EXPENSE_TYPE);

    return $avg ?? 0.0;
  }

  /**
   * @return float|null null — нет ни одной операции данного типа в окне
   */
  private function getAverageMonthlyByType(PDO $pdo, int $userId, int $months, string $type): ?float
  {
    $safeMonths = max(1, min($months, 36));
    $range = $this->resolveRollingMonthsRange($safeMonths);

    $stmt = $pdo->prepare(
      'SELECT
        COALESCE(SUM(t.amount), 0) AS total_sum,
        COUNT(DISTINCT DATE_FORMAT(t.date, \'%Y-%m\')) AS month_count
      FROM transactions t
      INNER JOIN categories c ON c.id = t.category_id
      WHERE t.user_id = :user_id
        AND c.type = :type
        AND t.date BETWEEN :start_date AND :end_date'
    );
    $stmt->execute([
      'user_id' => $userId,
      'type' => $type,
      'start_date' => $range['start_date'],
      'end_date' => $range['end_date'],
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return null;
    }

    $monthCount = isset($row['month_count']) ? (int) $row['month_count'] : 0;
    if ($monthCount <= 0) {
      return null;
    }

    $total = isset($row['total_sum']) ? (float) $row['total_sum'] : 0.0;

    return round($total / $monthCount, 2);
  }

  /**
   * Окно «последние N месяцев»: как у периода «месяц» в дашборде, но N раз.
   *
   * @return array{start_date:string, end_date:string}
   */
  private function resolveRollingMonthsRange(int $months): array
  {
    $safeMonths = max(1, min($months, 36));
    $today = new \DateTimeImmutable('today');
    $startDate = $today->modify('-' . $safeMonths . ' months')->modify('+1 day');

    return [
      'start_date' => $startDate->format('Y-m-d'),
      'end_date' => $today->format('Y-m-d'),
    ];
  }

  /**
   * @return array{start_date:string, end_date:string}
   */
  private function resolveDateRangeForPeriod(string $period): array
  {
    $normalizedPeriod = $this->normalizePeriod($period);
    $today = new \DateTimeImmutable('today');
    $startDate = match ($normalizedPeriod) {
      self::PERIOD_WEEK => $today->modify('-6 days'),
      self::PERIOD_MONTH => $today->modify('-1 month +1 day'),
      self::PERIOD_YEAR => $today->modify('-1 year +1 day'),
      default => $today->modify('-1 month +1 day'),
    };

    return [
      'start_date' => $startDate->format('Y-m-d'),
      'end_date' => $today->format('Y-m-d'),
    ];
  }

  private function resolveMonthCountForPeriod(string $period): int
  {
    $normalizedPeriod = $this->normalizePeriod($period);

    return match ($normalizedPeriod) {
      self::PERIOD_WEEK => 1,
      self::PERIOD_MONTH => 1,
      self::PERIOD_YEAR => 12,
      default => 1,
    };
  }

  public function create(PDO $pdo, int $userId, int $categoryId, string $amount, string $date, string $comment): bool
  {
    $stmt = $pdo->prepare(
      'INSERT INTO transactions (user_id, category_id, amount, date, comment)
       VALUES (:user_id, :category_id, :amount, :date, :comment)'
    );

    return $stmt->execute([
      'user_id' => $userId,
      'category_id' => $categoryId,
      'amount' => $amount,
      'date' => $date,
      'comment' => $comment,
    ]);
  }

  /**
   * @return array{
   *   id:int,
   *   user_id:int,
   *   category_id:int,
   *   amount:string,
   *   date:string,
   *   comment:string,
   *   category_type:string
   * }|null
   */
  public function findByIdForUser(PDO $pdo, int $transactionId, int $userId): ?array
  {
    $stmt = $pdo->prepare(
      'SELECT t.id, t.user_id, t.category_id, t.amount, t.date, t.comment, c.type AS category_type
       FROM transactions t
       INNER JOIN categories c ON c.id = t.category_id
       WHERE t.id = :id AND t.user_id = :user_id
       LIMIT 1'
    );
    $stmt->execute([
      'id' => $transactionId,
      'user_id' => $userId,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
      return null;
    }

    $id = isset($row['id']) ? (int) $row['id'] : 0;
    $ownerId = isset($row['user_id']) ? (int) $row['user_id'] : 0;
    $categoryId = isset($row['category_id']) ? (int) $row['category_id'] : 0;
    if ($id <= 0 || $ownerId <= 0 || $categoryId <= 0) {
      return null;
    }

    return [
      'id' => $id,
      'user_id' => $ownerId,
      'category_id' => $categoryId,
      'amount' => (string) ($row['amount'] ?? '0'),
      'date' => (string) ($row['date'] ?? ''),
      'comment' => (string) ($row['comment'] ?? ''),
      'category_type' => (string) ($row['category_type'] ?? ''),
    ];
  }

  public function updateForUser(
    PDO $pdo,
    int $transactionId,
    int $userId,
    int $categoryId,
    string $amount,
    string $date,
    string $comment
  ): bool {
    $stmt = $pdo->prepare(
      'UPDATE transactions
       SET category_id = :category_id, amount = :amount, date = :date, comment = :comment
       WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
      'category_id' => $categoryId,
      'amount' => $amount,
      'date' => $date,
      'comment' => $comment,
      'id' => $transactionId,
      'user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
  }

  public function deleteForUser(PDO $pdo, int $transactionId, int $userId): bool
  {
    $stmt = $pdo->prepare(
      'DELETE FROM transactions
       WHERE id = :id AND user_id = :user_id'
    );
    $stmt->execute([
      'id' => $transactionId,
      'user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
  }
}
