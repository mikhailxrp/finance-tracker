<?php

declare(strict_types=1);

$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$activeCredits = isset($activeCredits) && is_array($activeCredits) ? $activeCredits : [];
$closedCredits = isset($closedCredits) && is_array($closedCredits) ? $closedCredits : [];
$formError = isset($formError) && is_string($formError) ? $formError : null;
$formNotice = isset($formNotice) && is_string($formNotice) ? $formNotice : null;
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$editError = isset($editError) && is_string($editError) ? $editError : null;
$editOldInput = isset($editOldInput) && is_array($editOldInput) ? $editOldInput : [];

$defaultStartDate = (new DateTimeImmutable('today'))->format('Y-m-d');
$formTitle = isset($oldInput['title']) ? (string) $oldInput['title'] : '';
$formTotal = isset($oldInput['total_amount']) ? (string) $oldInput['total_amount'] : '';
$formRate = isset($oldInput['interest_rate']) ? (string) $oldInput['interest_rate'] : '';
$formStart = isset($oldInput['start_date']) ? (string) $oldInput['start_date'] : $defaultStartDate;
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Кредиты — FinanceTracker</title>
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
    <link rel="stylesheet" href="/assets/css/savings.css">
    <link rel="stylesheet" href="/assets/css/credits.css">
  </head>
  <?php
  $shouldOpenEditModal = $editError !== null && $editError !== '' && isset($editOldInput['credit_id']) && $editOldInput['credit_id'] !== '';
  ?>
  <body
    class="transactions-page credits-page"
    data-credits-period="<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
    data-credit-edit-error="<?= $shouldOpenEditModal ? '1' : '0' ?>"
  >
    <?php require __DIR__ . '/layout/header.php'; ?>

    <main class="transactions-main">
      <?php $activeAction = 'credits'; ?>
      <?php $showDashboardLink = true; ?>
      <?php require __DIR__ . '/components/quick-actions.php'; ?>

      <?php if ($formNotice !== null && $formNotice !== ''): ?>
        <p class="transactions-alert transactions-alert--success" role="status">
          <?= htmlspecialchars($formNotice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </p>
      <?php endif; ?>

      <?php if ($formError !== null && $formError !== ''): ?>
        <p class="transactions-alert transactions-alert--error" role="alert">
          <?= htmlspecialchars($formError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </p>
      <?php endif; ?>

      <section class="transactions-card credits-section" aria-label="Активные кредиты">
        <h1 class="transactions-title">Кредиты</h1>

        <div class="credits-cards">
          <?php foreach ($activeCredits as $credit): ?>
            <?php if (!is_array($credit)): ?>
              <?php continue; ?>
            <?php endif; ?>
            <?php require __DIR__ . '/components/credit-card.php'; ?>
          <?php endforeach; ?>

          <button
            type="button"
            class="credits-card-add"
            data-credit-modal-open
            aria-haspopup="dialog"
            aria-controls="credit-create-modal"
          >
            <span class="credits-card-add__plus" aria-hidden="true">+</span>
            <span class="credits-card-add__label">Добавить кредит</span>
          </button>
        </div>
      </section>

      <?php if (count($closedCredits) > 0): ?>
        <section class="transactions-card credits-section credits-section--closed" aria-label="Закрытые кредиты">
          <h2 class="credits-closed-title">Закрытые кредиты</h2>
          <div class="credits-cards">
            <?php foreach ($closedCredits as $credit): ?>
              <?php if (!is_array($credit)): ?>
                <?php continue; ?>
              <?php endif; ?>
              <?php require __DIR__ . '/components/credit-card.php'; ?>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    </main>

    <div class="goal-modal" id="credit-create-modal" role="dialog" aria-modal="true" aria-labelledby="credit-create-modal-title" hidden>
      <div class="goal-modal__backdrop" data-credit-modal-close></div>
      <div class="goal-modal__content">
        <button type="button" class="goal-modal__close" data-credit-modal-close aria-label="Закрыть окно">×</button>
        <h2 class="goal-modal__title" id="credit-create-modal-title">Новый кредит</h2>

        <form
          method="POST"
          action="/credits/create?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
          class="goal-form"
          data-submit-loading
          data-loading-text="Сохранение..."
        >
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

          <label class="goal-form__field">
            <span class="goal-form__label">Название</span>
            <input class="goal-form__input" type="text" name="title" maxlength="150" required value="<?= htmlspecialchars($formTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          </label>

          <label class="goal-form__field">
            <span class="goal-form__label">Сумма кредита (₽)</span>
            <input class="goal-form__input" type="number" name="total_amount" min="0.01" step="0.01" required value="<?= htmlspecialchars($formTotal, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          </label>

          <label class="goal-form__field">
            <span class="goal-form__label">Процент годовых (%)</span>
            <input class="goal-form__input" type="number" name="interest_rate" min="0" max="200" step="0.01" required value="<?= htmlspecialchars($formRate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          </label>

          <label class="goal-form__field">
            <span class="goal-form__label">Дата начала</span>
            <input class="goal-form__input" type="date" name="start_date" required value="<?= htmlspecialchars($formStart, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          </label>

          <button type="submit" class="transactions-submit">Сохранить кредит</button>
        </form>
      </div>
    </div>

    <div class="goal-modal" id="credit-edit-modal" role="dialog" aria-modal="true" aria-labelledby="credit-edit-modal-title" hidden>
      <div class="goal-modal__backdrop" data-credit-edit-close></div>
      <div class="goal-modal__content">
        <button type="button" class="goal-modal__close" data-credit-edit-close aria-label="Закрыть окно">×</button>
        <h2 class="goal-modal__title" id="credit-edit-modal-title">Редактировать кредит</h2>

        <form
          method="POST"
          action="/credits/update?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
          class="goal-form"
          data-submit-loading
          data-loading-text="Сохранение..."
        >
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          <input type="hidden" name="credit_id" id="credit-edit-id" value="<?= htmlspecialchars((string) ($editOldInput['credit_id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

          <label class="goal-form__field">
            <span class="goal-form__label">Название</span>
            <input class="goal-form__input" type="text" name="title" id="credit-edit-title" maxlength="150" required value="<?= htmlspecialchars((string) ($editOldInput['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          </label>

          <label class="goal-form__field">
            <span class="goal-form__label">Сумма кредита (₽)</span>
            <input class="goal-form__input" type="number" name="total_amount" id="credit-edit-total" min="0.01" step="0.01" required value="<?= htmlspecialchars((string) ($editOldInput['total_amount'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          </label>

          <label class="goal-form__field">
            <span class="goal-form__label">Процент годовых (%)</span>
            <input class="goal-form__input" type="number" name="interest_rate" id="credit-edit-rate" min="0" max="200" step="0.01" required value="<?= htmlspecialchars((string) ($editOldInput['interest_rate'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          </label>

          <label class="goal-form__field">
            <span class="goal-form__label">Дата начала</span>
            <input class="goal-form__input" type="date" name="start_date" id="credit-edit-start" required value="<?= htmlspecialchars((string) ($editOldInput['start_date'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          </label>

          <button type="submit" class="transactions-submit">Сохранить изменения</button>
        </form>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="/assets/js/form-submit.js"></script>
    <script type="module" src="/assets/js/credits.js"></script>
  </body>
</html>
