<?php

declare(strict_types=1);

$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$nameError = isset($nameError) && is_string($nameError) ? $nameError : '';
$nameSuccess = isset($nameSuccess) && is_string($nameSuccess) ? $nameSuccess : '';
$passwordError = isset($passwordError) && is_string($passwordError) ? $passwordError : '';
$passwordSuccess = isset($passwordSuccess) && is_string($passwordSuccess) ? $passwordSuccess : '';
$categoryError = isset($categoryError) && is_string($categoryError) ? $categoryError : '';
$categorySuccess = isset($categorySuccess) && is_string($categorySuccess) ? $categorySuccess : '';
$nameInput = isset($nameInput) && is_string($nameInput) ? $nameInput : '';
$createdAt = isset($createdAt) && is_string($createdAt) ? $createdAt : '';
$userName = isset($userName) && is_string($userName) ? $userName : '';
$userEmail = isset($userEmail) && is_string($userEmail) ? $userEmail : '';
$stats = isset($stats) && is_array($stats) ? $stats : [];
$categories = isset($categories) && is_array($categories) ? $categories : [];

$transactionsCount = isset($stats['transactions']) ? (int) $stats['transactions'] : 0;
$goalsCount = isset($stats['active_goals']) ? (int) $stats['active_goals'] : 0;
$aiCount = isset($stats['ai_strategies']) ? (int) $stats['ai_strategies'] : 0;

$incomeCategories = isset($categories['income']) && is_array($categories['income']) ? $categories['income'] : [];
$expenseCategories = isset($categories['expense']) && is_array($categories['expense']) ? $categories['expense'] : [];

$incomeSystemCategories = isset($incomeCategories['system']) && is_array($incomeCategories['system']) ? $incomeCategories['system'] : [];
$incomeCustomCategories = isset($incomeCategories['custom']) && is_array($incomeCategories['custom']) ? $incomeCategories['custom'] : [];
$expenseSystemCategories = isset($expenseCategories['system']) && is_array($expenseCategories['system']) ? $expenseCategories['system'] : [];
$expenseCustomCategories = isset($expenseCategories['custom']) && is_array($expenseCategories['custom']) ? $expenseCategories['custom'] : [];
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Профиль — FinanceTracker</title>
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
    <link rel="stylesheet" href="/assets/css/profile.css">
  </head>
  <body class="transactions-page profile-page">
    <?php require __DIR__ . '/components/header.php'; ?>

    <main class="transactions-main profile-main">
      <section class="profile-layout" aria-label="Профиль пользователя">
        <article class="profile-card">
          <div class="profile-card__avatar" aria-hidden="true">
            <?= htmlspecialchars(userInitialsFromFullName($userName), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
          </div>
          <h1 class="profile-card__name"><?= htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
          <p class="profile-card__email"><?= htmlspecialchars($userEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          <?php if ($createdAt !== ''): ?>
            <p class="profile-card__since">
              Участник с <?= htmlspecialchars($createdAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </p>
          <?php endif; ?>

          <ul class="profile-stats" aria-label="Статистика пользователя">
            <li class="profile-stats__item">
              <span class="profile-stats__label">Транзакций</span>
              <span class="profile-stats__value"><?= htmlspecialchars((string) $transactionsCount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            </li>
            <li class="profile-stats__item">
              <span class="profile-stats__label">Активных целей</span>
              <span class="profile-stats__value"><?= htmlspecialchars((string) $goalsCount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            </li>
            <li class="profile-stats__item">
              <span class="profile-stats__label">Стратегий AI</span>
              <span class="profile-stats__value"><?= htmlspecialchars((string) $aiCount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            </li>
          </ul>
        </article>

        <article class="profile-settings">
          <?php if ($nameSuccess !== ''): ?>
            <p class="transactions-alert transactions-alert--success" role="status">
              <?= htmlspecialchars($nameSuccess, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </p>
          <?php endif; ?>
          <?php if ($nameError !== ''): ?>
            <p class="transactions-alert transactions-alert--error" role="alert">
              <?= htmlspecialchars($nameError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </p>
          <?php endif; ?>
          <?php if ($passwordSuccess !== ''): ?>
            <p class="transactions-alert transactions-alert--success" role="status">
              <?= htmlspecialchars($passwordSuccess, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </p>
          <?php endif; ?>
          <?php if ($passwordError !== ''): ?>
            <p class="transactions-alert transactions-alert--error" role="alert">
              <?= htmlspecialchars($passwordError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </p>
          <?php endif; ?>
          <?php if ($categorySuccess !== ''): ?>
            <p class="transactions-alert transactions-alert--success" role="status">
              <?= htmlspecialchars($categorySuccess, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </p>
          <?php endif; ?>
          <?php if ($categoryError !== ''): ?>
            <p class="transactions-alert transactions-alert--error" role="alert">
              <?= htmlspecialchars($categoryError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </p>
          <?php endif; ?>

          <section class="profile-section" aria-label="Личные данные">
            <h2 class="profile-section__title">Личные данные</h2>
            <form class="profile-form" method="POST" action="/profile/update-name">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

              <label class="profile-form__field">
                <span class="profile-form__label">Имя</span>
                <input
                  class="profile-form__input"
                  type="text"
                  name="name"
                  maxlength="100"
                  required
                  value="<?= htmlspecialchars($nameInput, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                >
              </label>

              <label class="profile-form__field">
                <span class="profile-form__label">Email</span>
                <input
                  class="profile-form__input"
                  type="email"
                  value="<?= htmlspecialchars($userEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                  readonly
                  aria-describedby="profile-email-note"
                >
              </label>
              <p id="profile-email-note" class="profile-form__hint">Смена email недоступна в MVP.</p>

              <button class="profile-form__submit" type="submit">Сохранить</button>
            </form>
          </section>

          <section class="profile-section" aria-label="Смена пароля">
            <h2 class="profile-section__title">Безопасность</h2>
            <form class="profile-form" method="POST" action="/profile/change-password">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

              <label class="profile-form__field">
                <span class="profile-form__label">Текущий пароль</span>
                <div class="password-wrap">
                  <input class="profile-form__input" type="password" name="old_password" minlength="8" required>
                  <button class="password-eye" type="button" aria-label="Показать пароль">◉</button>
                </div>
              </label>

              <label class="profile-form__field">
                <span class="profile-form__label">Новый пароль</span>
                <div class="password-wrap">
                  <input class="profile-form__input" type="password" name="new_password" minlength="8" required>
                  <button class="password-eye" type="button" aria-label="Показать пароль">◉</button>
                </div>
              </label>

              <label class="profile-form__field">
                <span class="profile-form__label">Подтвердите пароль</span>
                <div class="password-wrap">
                  <input class="profile-form__input" type="password" name="new_password_confirm" minlength="8" required>
                  <button class="password-eye" type="button" aria-label="Показать пароль">◉</button>
                </div>
              </label>

              <button class="profile-form__submit" type="submit">Изменить пароль</button>
            </form>
          </section>

          <section class="profile-section" id="profile-categories" aria-label="Управление категориями">
            <div class="profile-section__head">
              <h2 class="profile-section__title">Мои категории</h2>
              <button type="button" class="profile-form__submit profile-form__submit--secondary" data-category-open>
                + Добавить категорию
              </button>
            </div>

            <div class="profile-categories-group">
              <h3 class="profile-categories-group__title">Доходы</h3>
              <div class="profile-categories-grid">
                <?php foreach ($incomeSystemCategories as $category): ?>
                  <?php require __DIR__ . '/components/category-card.php'; ?>
                <?php endforeach; ?>
                <?php foreach ($incomeCustomCategories as $category): ?>
                  <?php require __DIR__ . '/components/category-card.php'; ?>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="profile-categories-group">
              <h3 class="profile-categories-group__title">Расходы</h3>
              <div class="profile-categories-grid">
                <?php foreach ($expenseSystemCategories as $category): ?>
                  <?php require __DIR__ . '/components/category-card.php'; ?>
                <?php endforeach; ?>
                <?php foreach ($expenseCustomCategories as $category): ?>
                  <?php require __DIR__ . '/components/category-card.php'; ?>
                <?php endforeach; ?>
              </div>
            </div>
          </section>
        </article>
      </section>
    </main>

    <?php require __DIR__ . '/components/category-form-modal.php'; ?>

    <script src="/assets/js/header-dropdown.js"></script>
    <script type="module" src="/assets/js/password-toggle.js"></script>
    <script src="/assets/js/categories.js"></script>
  </body>
</html>
