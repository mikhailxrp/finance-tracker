<?php

declare(strict_types=1);

$statTitle = isset($statTitle) && is_string($statTitle) ? $statTitle : '';
$statValue = isset($statValue) && is_string($statValue) ? $statValue : '0.00';
$statDescription = isset($statDescription) && is_string($statDescription) ? $statDescription : '';
$statIconName = isset($statIconName) && is_string($statIconName) ? $statIconName : '';

$iconSvg = match ($statIconName) {
  'trend' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 15l4-4 3 3 5-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  'trend-down' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l4 4 3-3 5 6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  'source' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v11H4zM9 7V5h6v2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  'category' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.5l9-7 9 7M5.5 9.5V20h13V9.5M9.5 20v-6h5V20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  'money' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M16 8.5c0-1.4-1.8-2.5-4-2.5s-4 1.1-4 2.5 1.8 2.5 4 2.5 4 1.1 4 2.5-1.8 2.5-4 2.5-4-1.1-4-2.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
  default => '',
};
?>
<article class="transactions-card stat-card" aria-label="<?= htmlspecialchars($statTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <p class="transactions-label stat-card__label">
    <?php if ($iconSvg !== ''): ?>
      <span class="stat-card__icon" aria-hidden="true"><?= $iconSvg ?></span>
    <?php endif; ?>
    <span><?= htmlspecialchars($statTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
  </p>
  <p class="transactions-title transactions-title--sub stat-card__value"><?= htmlspecialchars($statValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php if ($statDescription !== ''): ?>
    <p class="transactions-empty stat-card__description"><?= htmlspecialchars($statDescription, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php endif; ?>
</article>
