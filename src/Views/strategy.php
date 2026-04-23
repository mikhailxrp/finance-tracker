<?php

declare(strict_types=1);

$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'all';
$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$formError = isset($formError) && is_string($formError) ? $formError : '';
$formSuccess = isset($formSuccess) && is_string($formSuccess) ? $formSuccess : '';
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$insufficientData = isset($insufficientData) && is_array($insufficientData) ? $insufficientData : [];
$latestStrategy = isset($latestStrategy) && is_array($latestStrategy) ? $latestStrategy : [];
$strategyPresets = isset($strategyPresets) && is_array($strategyPresets) ? $strategyPresets : [];
$strategyHistory = isset($strategyHistory) && is_array($strategyHistory) ? $strategyHistory : [];
$hasHistory = isset($hasHistory) && $hasHistory === true;
$historyPeriod = $selectedPeriod;
$navigationPeriod = in_array($historyPeriod, ['week', 'month', 'year'], true) ? $historyPeriod : 'month';
$periodLabels = [
  'all' => 'Все',
  'week' => 'Неделя',
  'month' => 'Месяц',
  'year' => 'Год',
];
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>AI-стратегия — FinanceTracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    >
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap"
      rel="stylesheet"
    >
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/typography.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/strategy.css">
  </head>
  <body class="transactions-page strategy-page">
    <?php $selectedPeriod = $navigationPeriod; ?>
    <?php require __DIR__ . '/components/header.php'; ?>

    <main class="transactions-main strategy-main">
      <?php $activeAction = 'strategy'; ?>
      <?php $showDashboardLink = true; ?>
      <?php require __DIR__ . '/components/quick-actions.php'; ?>

      <?php require __DIR__ . '/components/ai-cta-card.php'; ?>

      <?php if ($formSuccess !== ''): ?>
        <div class="flash-message flash-success" role="status" aria-live="polite">
          <span class="flash-message__icon" aria-hidden="true">✓</span>
          <span><?= htmlspecialchars($formSuccess, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
        </div>
      <?php endif; ?>
      <?php if ($formError !== ''): ?>
        <div class="flash-message flash-error" role="alert" aria-live="assertive">
          <span class="flash-message__icon" aria-hidden="true">⚠</span>
          <span><?= htmlspecialchars($formError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
        </div>
      <?php endif; ?>

      <?php require __DIR__ . '/components/strategy-insufficient-data.php'; ?>
      <?php if ($insufficientData === []): ?>
        <?php require __DIR__ . '/components/strategy-result.php'; ?>
      <?php endif; ?>

      <section class="strategy-history" aria-label="История стратегий">
        <h2 class="strategy-history__title">История стратегий</h2>
        <nav class="strategy-history__periods" aria-label="Фильтр истории по периоду">
          <?php foreach ($periodLabels as $periodKey => $periodLabel): ?>
            <?php
            $isCurrentPeriod = $historyPeriod === $periodKey;
            $periodClass = $isCurrentPeriod
              ? 'strategy-history__period strategy-history__period--active'
              : 'strategy-history__period';
            ?>
            <a
              class="<?= htmlspecialchars($periodClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
              href="/strategy?period=<?= htmlspecialchars($periodKey, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
              aria-current="<?= $isCurrentPeriod ? 'page' : 'false' ?>"
            >
              <?= htmlspecialchars($periodLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </a>
          <?php endforeach; ?>
        </nav>
        <div class="strategy-history__list">
          <?php if ($strategyHistory === []): ?>
            <div class="strategy-history__empty strategy-empty-state" role="status" aria-live="polite">
              <?php if (!$hasHistory): ?>
                <span class="strategy-empty-state__icon" aria-hidden="true">📝</span>
                <span class="strategy-empty-state__text">Пока нет сохранённых стратегий</span>
              <?php else: ?>
                <span class="strategy-empty-state__icon" aria-hidden="true">🔍</span>
                <span class="strategy-empty-state__text">Нет стратегий за выбранный период</span>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <?php foreach ($strategyHistory as $index => $item): ?>
              <?php if (!is_array($item)): ?>
                <?php continue; ?>
              <?php endif; ?>
              <?php
              $historyItem = $item;
              $historyId = 'strategy-history-' . (string) $index;
              require __DIR__ . '/components/strategy-history-item.php';
              ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="/assets/js/strategy.js"></script>
  </body>
</html>
