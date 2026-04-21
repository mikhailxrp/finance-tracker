<?php

declare(strict_types=1);

$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$goals = isset($goals) && is_array($goals) ? $goals : [];
$formError = isset($formError) && is_string($formError) ? $formError : null;
$formNotice = isset($formNotice) && is_string($formNotice) ? $formNotice : null;
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$purchasePlans = isset($purchasePlans) && is_array($purchasePlans) ? $purchasePlans : [];
$purchasePlanError = isset($purchasePlanError) && is_string($purchasePlanError) ? $purchasePlanError : null;
$purchasePlanOldInput = isset($purchasePlanOldInput) && is_array($purchasePlanOldInput) ? $purchasePlanOldInput : [];
$availableTerms = isset($availableTerms) && is_array($availableTerms) ? $availableTerms : [];
$ppFormTitle = isset($purchasePlanOldInput['title']) ? (string) $purchasePlanOldInput['title'] : '';
$ppFormAmount = isset($purchasePlanOldInput['target_amount']) ? (string) $purchasePlanOldInput['target_amount'] : '';
$ppFormTermRaw = isset($purchasePlanOldInput['term_months']) ? (string) $purchasePlanOldInput['term_months'] : '';
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Цели — FinanceTracker</title>
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
    <link rel="stylesheet" href="/assets/css/purchase-plans.css">
  </head>
  <body class="transactions-page savings-page" data-savings-period="<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <?php require __DIR__ . '/components/header.php'; ?>

    <main class="transactions-main">
      <?php $activeAction = 'goals'; ?>
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

      <?php if ($purchasePlanError !== null && $purchasePlanError !== ''): ?>
        <p class="transactions-alert transactions-alert--error" role="alert">
          <?= htmlspecialchars($purchasePlanError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </p>
      <?php endif; ?>

      <section class="transactions-card savings-section" aria-label="Список целей">
        <h1 class="transactions-title">Цели накопления</h1>

        <div class="goals-grid">
          <?php foreach ($goals as $goal): ?>
            <?php if (!is_array($goal)): ?>
              <?php continue; ?>
            <?php endif; ?>
            <?php require __DIR__ . '/components/goal-card.php'; ?>
          <?php endforeach; ?>

          <?php require __DIR__ . '/components/goal-card-add.php'; ?>
        </div>
      </section>

      <section class="transactions-card purchase-planner-section" aria-label="Планировщик покупки">
        <h2 class="transactions-title">Планировщик покупки</h2>

        <form
          method="POST"
          action="/purchase-plans/create?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
          class="goal-form purchase-planner-form"
          data-submit-loading
          data-loading-text="Сохранение..."
        >
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

          <label class="goal-form__field">
            <span class="goal-form__label">Название покупки</span>
            <input
              class="goal-form__input"
              type="text"
              name="title"
              maxlength="150"
              required
              value="<?= htmlspecialchars($ppFormTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
            >
          </label>

          <label class="goal-form__field">
            <span class="goal-form__label">Стоимость</span>
            <input
              class="goal-form__input"
              type="number"
              name="target_amount"
              min="0.01"
              step="0.01"
              required
              value="<?= htmlspecialchars($ppFormAmount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
            >
          </label>

          <label class="goal-form__field">
            <span class="goal-form__label">Желаемый срок накопления</span>
            <select class="goal-form__input" name="term_months" required>
              <?php foreach ($availableTerms as $termOption): ?>
                <?php if (!is_int($termOption) && !is_string($termOption)): ?>
                  <?php continue; ?>
                <?php endif; ?>
                <?php
                  $termInt = (int) $termOption;
                  $termStr = (string) $termInt;
                  $isSelected = $ppFormTermRaw !== ''
                    ? $ppFormTermRaw === $termStr
                    : $termInt === 12;
                ?>
                <option value="<?= htmlspecialchars($termStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"<?= $isSelected ? ' selected' : '' ?>>
                  <?= htmlspecialchars($termStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> мес.
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <button type="submit" class="transactions-submit">Сохранить план</button>
        </form>

        <div class="purchase-plans-list">
          <?php foreach ($purchasePlans as $plan): ?>
            <?php if (!is_array($plan)): ?>
              <?php continue; ?>
            <?php endif; ?>
            <?php require __DIR__ . '/components/purchase-plan-card.php'; ?>
          <?php endforeach; ?>
        </div>
      </section>
    </main>

    <?php require __DIR__ . '/components/goal-form-modal.php'; ?>
    <?php require __DIR__ . '/components/goal-edit-modal.php'; ?>
    <?php require __DIR__ . '/components/goal-confirm-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="/assets/js/form-submit.js"></script>
    <script type="module" src="/assets/js/savings.js"></script>
  </body>
</html>
