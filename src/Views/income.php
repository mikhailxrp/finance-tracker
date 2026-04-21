<?php

declare(strict_types=1);

$notice = isset($notice) && is_string($notice) ? $notice : null;
$error = isset($error) && is_string($error) ? $error : null;
$pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Доходы';
$currentPath = isset($currentPath) && is_string($currentPath) ? $currentPath : '/income';
$transactions = isset($transactions) && is_array($transactions) ? $transactions : [];
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$statsData = isset($statsData) && is_array($statsData) ? $statsData : [];
$chartData = isset($chartData) && is_array($chartData) ? $chartData : [];
$transactionsByMonth = isset($transactionsByMonth) && is_array($transactionsByMonth) ? $transactionsByMonth : [];
$totalIncome = isset($statsData['total']) ? (string) $statsData['total'] : '0.00';
$topSourceName = isset($statsData['top_source_name']) ? (string) $statsData['top_source_name'] : '—';
$avgPerMonth = isset($statsData['avg_per_month']) ? (string) $statsData['avg_per_month'] : '0';
$safeChartData = [
  'months' => isset($chartData['months']) && is_array($chartData['months']) ? $chartData['months'] : [],
  'series' => isset($chartData['series']) && is_array($chartData['series']) ? $chartData['series'] : [],
];
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    >
    <link
      href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap"
      rel="stylesheet"
    >
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/typography.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/form-submit.css">
  </head>
  <body class="transactions-page income-page">
    <?php require __DIR__ . '/components/header.php'; ?>

    <main class="transactions-main">
      <nav class="transactions-tabs" aria-label="Разделы транзакций">
        <a class="transactions-tab" href="/dashboard?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">← Дашборд</a>
        <a class="transactions-tab transactions-tab--active" href="/income?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Доходы</a>
        <a class="transactions-tab" href="/expenses?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Расходы</a>
      </nav>

      <?php if ($notice !== null && $notice !== ''): ?>
        <p class="transactions-alert transactions-alert--success" role="status">
          <?= htmlspecialchars($notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </p>
      <?php endif; ?>

      <?php if ($error !== null && $error !== ''): ?>
        <p class="transactions-alert transactions-alert--error" role="alert">
          <?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </p>
      <?php endif; ?>

      <section class="income-stats" aria-label="Сводка доходов">
        <?php
        $statTitle = 'Итого за период';
        $statValue = number_format((float) $totalIncome, 0, '.', ' ');
        $statDescription = '';
        $statIconName = 'trend';
        require __DIR__ . '/components/stat-card.php';
        ?>

        <?php
        $statTitle = 'Основной источник';
        $statValue = $topSourceName;
        $statDescription = '';
        $statIconName = 'source';
        require __DIR__ . '/components/stat-card.php';
        ?>

        <?php
        $statTitle = 'Средний доход в месяц';
        $statValue = number_format((float) $avgPerMonth, 0, '.', ' ');
        $statDescription = '';
        $statIconName = 'money';
        require __DIR__ . '/components/stat-card.php';
        ?>
      </section>

      <?php $transactionFormVariant = 'income'; ?>
      <?php require __DIR__ . '/components/transaction-form.php'; ?>

      <section class="income-layout" aria-label="Аналитика и список доходов">
        <div class="transactions-card income-layout__chart">
          <h2 class="transactions-title transactions-title--sub">Доходы по источникам</h2>
          <div id="incomeByCategoryChart" class="dashboard-chart-area" aria-label="Bar график доходов по категориям"></div>
        </div>

        <div class="transactions-card income-layout__list" aria-label="Список доходов по месяцам">
          <div class="transactions-list__header">
            <h2 class="transactions-title transactions-title--sub">Доходы за выбранный период</h2>
          </div>

          <?php if ($transactionsByMonth === []): ?>
            <p class="transactions-empty">Доходов за выбранный период пока нет. Добавьте первую запись выше.</p>
          <?php else: ?>
            <?php foreach ($transactionsByMonth as $monthGroup): ?>
              <?php if (!is_array($monthGroup)): ?>
                <?php continue; ?>
              <?php endif; ?>
              <?php $monthLabel = isset($monthGroup['month_label']) ? (string) $monthGroup['month_label'] : ''; ?>
              <?php $monthTransactions = isset($monthGroup['transactions']) && is_array($monthGroup['transactions']) ? $monthGroup['transactions'] : []; ?>
              <?php if ($monthTransactions === []): ?>
                <?php continue; ?>
              <?php endif; ?>

              <section class="income-month-group" aria-label="<?= htmlspecialchars($monthLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <h3 class="income-month-group__title"><?= htmlspecialchars($monthLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                <ul class="transactions-list">
                  <?php foreach ($monthTransactions as $item): ?>
                    <?php if (!is_array($item)): ?>
                      <?php continue; ?>
                    <?php endif; ?>
                    <?php $transaction = $item; ?>
                    <?php $transactionRowVariant = 'compact'; ?>
                    <?php require __DIR__ . '/components/transaction-row.php'; ?>
                  <?php endforeach; ?>
                </ul>
              </section>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.0/dist/apexcharts.min.js"></script>
    <script>
      window.incomeChartData = <?= json_encode($safeChartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script type="module" src="/assets/js/form-submit.js"></script>
    <script type="module" src="/assets/js/income-charts.js"></script>
  </body>
</html>
