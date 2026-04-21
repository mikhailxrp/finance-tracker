<?php

declare(strict_types=1);

/**
 * Шапка приложения — подключать только на страницах после requireAuth().
 */

$userName = isset($userName) && is_string($userName) ? $userName : '';
$userEmail = isset($userEmail) && is_string($userEmail) ? $userEmail : '';
$userInitials = isset($userInitials) && is_string($userInitials) && $userInitials !== ''
  ? $userInitials
  : userInitialsFromFullName($userName);
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$currentPath = isset($currentPath) && is_string($currentPath) ? $currentPath : '/dashboard';

$safeName = htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars($userEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeInitials = htmlspecialchars($userInitials, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$periods = [
  'week' => 'Неделя',
  'month' => 'Месяц',
  'year' => 'Год',
];
?>
<header class="app-header" role="banner">
  <nav class="app-header__period" aria-label="Период отчёта">
    <div class="period-switch__track" role="group">
      <?php foreach ($periods as $periodKey => $periodLabel): ?>
        <?php if ($selectedPeriod === $periodKey): ?>
          <span class="period-switch__pill"><?= htmlspecialchars($periodLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
        <?php else: ?>
          <a
            href="<?= htmlspecialchars($currentPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>?period=<?= htmlspecialchars($periodKey, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
            class="period-switch__btn"
          >
            <?= htmlspecialchars($periodLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </nav>

  <div class="app-header__actions">
    <button
      type="button"
      class="app-header__theme"
      aria-label="Переключить тему"
    >
      <svg
        width="22"
        height="22"
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        <path
          d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"
          stroke="currentColor"
          stroke-width="1.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </button>

    <div class="app-header__profile">
      <div class="app-header__avatar" aria-hidden="true"><?= $safeInitials ?></div>
      <div class="app-header__meta">
        <p class="app-header__name"><?= $safeName ?></p>
        <p class="app-header__email"><?= $safeEmail ?></p>
      </div>
    </div>
  </div>
</header>
