<?php

declare(strict_types=1);

$totals = isset($totals) && is_array($totals) ? $totals : [];
$totalIncome = isset($totals['total_income']) ? (string) $totals['total_income'] : '0.00';
$totalExpense = isset($totals['total_expense']) ? (string) $totals['total_expense'] : '0.00';
$balance = isset($totals['balance']) ? (string) $totals['balance'] : '0.00';
$topExpenseCategory = isset($topExpenseCategory) && is_array($topExpenseCategory) ? $topExpenseCategory : null;
$recentTransactions = isset($recentTransactions) && is_array($recentTransactions) ? $recentTransactions : [];
$chartData = isset($chartData) && is_array($chartData) ? $chartData : [];
$dashboardError = isset($dashboardError) && is_string($dashboardError) ? $dashboardError : null;
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$periodLabels = [
  'week' => 'Неделя',
  'month' => 'Месяц',
  'year' => 'Год',
];
$safeChartData = [
  'expense_categories' => isset($chartData['expense_categories']) && is_array($chartData['expense_categories'])
    ? $chartData['expense_categories']
    : [],
  'daily_dynamics' => isset($chartData['daily_dynamics']) && is_array($chartData['daily_dynamics'])
    ? $chartData['daily_dynamics']
    : [],
];
$periodSuffix = $selectedPeriod === 'week'
  ? 'за неделю'
  : ($selectedPeriod === 'year' ? 'за год' : 'за месяц');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="utf-8">
  <title>Дашборд — FinanceTracker</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/typography.css">
  <link rel="stylesheet" href="/assets/css/header.css">
  <link rel="stylesheet" href="/assets/css/form-submit.css">
</head>

<body class="transactions-page">
  <?php require __DIR__ . '/components/header.php'; ?>

  <main class="transactions-main">
    <?php $activeAction = 'goals'; ?>
    <?php require __DIR__ . '/components/quick-actions.php'; ?>

    <section class="transactions-card" aria-label="Сводка дашборда">
      <h1 class="transactions-title">Дашборд</h1>

      <?php if ($dashboardError !== null && $dashboardError !== ''): ?>
        <p class="transactions-alert transactions-alert--error" role="alert">
          <?= htmlspecialchars($dashboardError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </p>
      <?php endif; ?>

      <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
          <?php
          $statTitle = 'Доходы ' . $periodSuffix;
          $statValue = $totalIncome . ' ₽';
          $statDescription = 'Сумма доходных транзакций за выбранный период.';
          require __DIR__ . '/components/stat-card.php';
          ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <?php
          $statTitle = 'Расходы ' . $periodSuffix;
          $statValue = $totalExpense . ' ₽';
          $statDescription = 'Сумма расходных транзакций за выбранный период.';
          require __DIR__ . '/components/stat-card.php';
          ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <?php
          $statTitle = 'Баланс ' . $periodSuffix;
          $statValue = $balance . ' ₽';
          $statDescription = 'Разница между доходами и расходами за выбранный период.';
          require __DIR__ . '/components/stat-card.php';
          ?>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
          <?php
          $hasTopCategory = $topExpenseCategory !== null
            && isset($topExpenseCategory['category_name'], $topExpenseCategory['amount']);
          $topCategoryName = $hasTopCategory ? (string) $topExpenseCategory['category_name'] : 'Нет данных';
          $topCategoryAmount = $hasTopCategory ? (string) $topExpenseCategory['amount'] : '0.00';
          $statTitle = 'Топ категория расходов ' . $periodSuffix;
          $statValue = $topCategoryName;
          $statDescription = $hasTopCategory
            ? ('Сумма: ' . $topCategoryAmount . ' ₽')
            : 'Расходные транзакции пока отсутствуют.';
          require __DIR__ . '/components/stat-card.php';
          ?>
        </div>
      </div>
    </section>

    <div class="dashboard-charts" role="region" aria-label="Графики дашборда">
      <section class="transactions-card dashboard-chart-card--line">
        <h2 class="transactions-title transactions-title--sub">Динамика доходов и расходов</h2>
        <p class="dashboard-chart-caption">Суммы по дням <?= htmlspecialchars($periodSuffix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
        <div id="dailyDynamicsChart" class="dashboard-chart-area" aria-label="График динамики доходов и расходов"></div>
      </section>

      <section class="transactions-card dashboard-chart-card--donut">
        <h2 class="transactions-title transactions-title--sub">Расходы по категориям</h2>
        <p class="dashboard-chart-caption">Top категорий расходов <?= htmlspecialchars($periodSuffix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>.</p>
        <div id="expenseCategoriesChart" class="dashboard-chart-area" aria-label="График расходов по категориям"></div>
      </section>
    </div>

    <section class="transactions-card" aria-label="Последние транзакции">
      <div class="transactions-list__header">
        <h2 class="transactions-title transactions-title--sub">Транзакции за выбранный период</h2>
        <a href="/income?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="transactions-link">Все транзакции →</a>
      </div>

      <?php if ($recentTransactions === []): ?>
        <p class="transactions-empty">Транзакций пока нет. Добавьте первую запись.</p>
      <?php else: ?>
        <ul class="transactions-list">
          <?php foreach ($recentTransactions as $item): ?>
            <?php if (!is_array($item)): ?>
              <?php continue; ?>
            <?php endif; ?>
            <?php $transaction = $item; ?>
            <?php require __DIR__ . '/components/transaction-row.php'; ?>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>



    <form method="POST" action="/logout" data-submit-loading data-loading-text="Выход...">
      <button type="submit" class="transactions-submit" data-loading-text="Выход...">Выйти</button>
    </form>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.0/dist/apexcharts.min.js"></script>
  <script>
    window.chartData = <?= json_encode($safeChartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
  <script type="module" src="/assets/js/form-submit.js"></script>
  <script type="module" src="/assets/js/charts.js"></script>
</body>

</html>