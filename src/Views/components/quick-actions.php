<?php

declare(strict_types=1);

$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$activeAction = isset($activeAction) && is_string($activeAction) ? $activeAction : '';
$showDashboardLink = isset($showDashboardLink) && $showDashboardLink === true;

$allowedActions = ['goals', 'strategy', 'credits', 'expense', 'income'];
if (!in_array($activeAction, $allowedActions, true)) {
  $activeAction = '';
}

$actions = [
  ...($showDashboardLink
    ? [[
      'id' => 'dashboard',
      'label' => 'Дашборд',
      'href' => '/dashboard?period=' . rawurlencode($selectedPeriod),
    ]]
    : []),
  [
    'id' => 'goals',
    'label' => 'Цели',
    'href' => '/savings?period=' . rawurlencode($selectedPeriod),
  ],
  [
    'id' => 'strategy',
    'label' => 'Финансовая стратегия',
    'href' => '/strategy?period=' . rawurlencode($selectedPeriod),
  ],
  [
    'id' => 'credits',
    'label' => 'Кредиты',
    'href' => '/credits?period=' . rawurlencode($selectedPeriod),
  ],
  [
    'id' => 'expense',
    'label' => 'Добавить расход',
    'href' => '/expenses?period=' . rawurlencode($selectedPeriod),
  ],
  [
    'id' => 'income',
    'label' => 'Добавить доход',
    'href' => '/income?period=' . rawurlencode($selectedPeriod),
  ],
];
?>
<section class="dashboard-actions" aria-label="Быстрые действия">
  <?php foreach ($actions as $action): ?>
    <?php
    $isActive = $activeAction === $action['id'];
    $modifier = $isActive ? 'dashboard-action--active' : 'dashboard-action--secondary';
    $classes = 'dashboard-action ' . $modifier;
    ?>
    <a
      class="<?= htmlspecialchars($classes, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      href="<?= htmlspecialchars((string) $action['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      aria-current="<?= $isActive ? 'page' : 'false' ?>"
    >
      <?= htmlspecialchars((string) $action['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </a>
  <?php endforeach; ?>
</section>
