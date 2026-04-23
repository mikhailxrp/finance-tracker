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

// Должен совпадать с `AuthController::CSRF_SESSION_KEY` — токен для POST /logout.
\ensureSessionStarted();
$__authCsrf = $_SESSION['auth_csrf'] ?? null;
if (!is_string($__authCsrf) || $__authCsrf === '') {
  $__authCsrf = bin2hex(random_bytes(32));
  $_SESSION['auth_csrf'] = $__authCsrf;
}

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

    <div class="app-header__profile-wrapper">
      <button
        type="button"
        class="app-header__profile-button"
        aria-label="Открыть меню профиля"
        aria-expanded="false"
        aria-haspopup="true"
        data-profile-toggle
      >
        <div class="app-header__profile">
          <div class="app-header__avatar" aria-hidden="true"><?= $safeInitials ?></div>
          <div class="app-header__meta">
            <p class="app-header__name"><?= $safeName ?></p>
            <p class="app-header__email"><?= $safeEmail ?></p>
          </div>
        </div>
        <svg
          class="app-header__profile-chevron"
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          aria-hidden="true"
        >
          <path
            d="M6 9l6 6 6-6"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>

      <div class="app-header__dropdown" data-profile-menu hidden>
        <a class="app-header__dropdown-item" href="/dashboard">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
            <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
            <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
            <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
          </svg>
          <span>Дашборд</span>
        </a>
        <a class="app-header__dropdown-item" href="/profile">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
            <path d="M5 20c0-4 3-7 7-7s7 3 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span>Профиль</span>
        </a>
        <form method="POST" action="/logout" class="app-header__dropdown-form">
          <input type="hidden" name="csrf" value="<?= e($__authCsrf) ?>">
          <button type="submit" class="app-header__dropdown-item app-header__dropdown-item--logout">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M16 17l5-5-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Выйти</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</header>
