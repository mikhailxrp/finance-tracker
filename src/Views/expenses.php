<?php

declare(strict_types=1);

$notice = isset($notice) && is_string($notice) ? $notice : null;
$error = isset($error) && is_string($error) ? $error : null;
$pageTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Расходы';
$currentPath = isset($currentPath) && is_string($currentPath) ? $currentPath : '/expenses';
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$statsData = isset($statsData) && is_array($statsData) ? $statsData : [];
$topCategories = isset($topCategories) && is_array($topCategories) ? $topCategories : [];
$transactionsByMonth = isset($transactionsByMonth) && is_array($transactionsByMonth) ? $transactionsByMonth : [];
$totalExpense = isset($statsData['total']) ? (string) $statsData['total'] : '0.00';
$topCategoryName = isset($statsData['top_category_name']) ? (string) $statsData['top_category_name'] : '—';
$avgPerMonth = isset($statsData['avg_per_month']) ? (string) $statsData['avg_per_month'] : '0';
$resolveExpenseBarColorClass = static function (string $rawColor): string {
  $color = strtoupper(trim($rawColor));

  return match ($color) {
    '#00C9A7' => 'top-expenses-list__bar--teal',
    '#4F8EF7' => 'top-expenses-list__bar--blue',
    '#F87171' => 'top-expenses-list__bar--red',
    '#A78BFA' => 'top-expenses-list__bar--purple',
    '#FBBF24' => 'top-expenses-list__bar--amber',
    '#FB923C' => 'top-expenses-list__bar--orange',
    '#34D399' => 'top-expenses-list__bar--green',
    default => 'top-expenses-list__bar--default',
  };
};
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
  <body class="transactions-page expenses-page">
    <?php require __DIR__ . '/components/header.php'; ?>

    <main class="transactions-main">
      <nav class="transactions-tabs" aria-label="Разделы транзакций">
        <a class="transactions-tab" href="/dashboard?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">← Дашборд</a>
        <a class="transactions-tab" href="/income?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Доходы</a>
        <a class="transactions-tab transactions-tab--active" href="/expenses?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Расходы</a>
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

      <section class="expenses-stats" aria-label="Сводка расходов">
        <?php
        $statTitle = 'Итого за период';
        $statValue = number_format((float) $totalExpense, 0, '.', ' ');
        $statDescription = '';
        $statIconName = 'trend-down';
        require __DIR__ . '/components/stat-card.php';
        ?>

        <?php
        $statTitle = 'Основная категория';
        $statValue = $topCategoryName;
        $statDescription = '';
        $statIconName = 'category';
        require __DIR__ . '/components/stat-card.php';
        ?>

        <?php
        $statTitle = 'Средние расходы в месяц';
        $statValue = number_format((float) $avgPerMonth, 0, '.', ' ');
        $statDescription = '';
        $statIconName = 'trend-down';
        require __DIR__ . '/components/stat-card.php';
        ?>
      </section>

      <?php $transactionFormVariant = 'expense'; ?>
      <?php require __DIR__ . '/components/transaction-form.php'; ?>

      <section class="expenses-layout" aria-label="Аналитика и список расходов">
        <div class="transactions-card expenses-layout__top">
          <h2 class="transactions-title transactions-title--sub">Топ трат</h2>
          <?php if ($topCategories === []): ?>
            <p class="transactions-empty">Нет расходов за выбранный период.</p>
          <?php else: ?>
            <ul class="top-expenses-list">
              <?php foreach ($topCategories as $item): ?>
                <?php if (!is_array($item)): ?>
                  <?php continue; ?>
                <?php endif; ?>
                <?php $categoryName = isset($item['category_name']) ? (string) $item['category_name'] : 'Без категории'; ?>
                <?php $amount = isset($item['amount']) ? (float) $item['amount'] : 0.0; ?>
                <?php $percentage = isset($item['percentage']) ? (float) $item['percentage'] : 0.0; ?>
                <?php $barColor = isset($item['category_color']) ? (string) $item['category_color'] : '#9CA3AF'; ?>
                <?php $safeWidth = max(0.0, min($percentage, 100.0)); ?>
                <?php $barColorClass = $resolveExpenseBarColorClass($barColor); ?>
                <li class="top-expenses-list__item">
                  <div class="top-expenses-list__head">
                    <span class="top-expenses-list__name"><?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                    <span class="top-expenses-list__meta">
                      <span class="top-expenses-list__percent"><?= htmlspecialchars((string) number_format($safeWidth, 0, '.', ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>%</span>
                      <span class="top-expenses-list__amount"><?= htmlspecialchars((string) number_format($amount, 0, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> ₽</span>
                    </span>
                  </div>
                  <progress
                    class="top-expenses-list__bar <?= htmlspecialchars($barColorClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    max="100"
                    value="<?= htmlspecialchars((string) $safeWidth, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    aria-label="Доля расходов категории <?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                  ></progress>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>

        <div class="transactions-card expenses-layout__list" aria-label="Список расходов по месяцам">
          <div class="transactions-list__header">
            <h2 class="transactions-title transactions-title--sub">Расходы за выбранный период</h2>
          </div>

          <?php if ($transactionsByMonth === []): ?>
            <p class="transactions-empty">Расходов за выбранный период пока нет. Добавьте первую запись выше.</p>
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

              <section class="expenses-month-group" aria-label="<?= htmlspecialchars($monthLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <h3 class="expenses-month-group__title"><?= htmlspecialchars($monthLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
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
    <script type="module" src="/assets/js/form-submit.js"></script>
  </body>
</html>
