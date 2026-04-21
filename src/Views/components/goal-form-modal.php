<?php

declare(strict_types=1);

$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];

$formTitle = isset($oldInput['title']) ? (string) $oldInput['title'] : '';
$formTargetAmount = isset($oldInput['target_amount']) ? (string) $oldInput['target_amount'] : '';
$formPeriod = isset($oldInput['period']) ? (string) $oldInput['period'] : 'month';
$formDeadline = isset($oldInput['deadline']) ? (string) $oldInput['deadline'] : '';
?>
<div class="goal-modal" id="goal-create-modal" role="dialog" aria-modal="true" aria-labelledby="goal-modal-title" hidden>
  <div class="goal-modal__backdrop" data-goal-modal-close></div>
  <div class="goal-modal__content">
    <button type="button" class="goal-modal__close" data-goal-modal-close aria-label="Закрыть модалку">×</button>
    <h2 class="goal-modal__title" id="goal-modal-title">Новая цель</h2>

    <form
      method="POST"
      action="/savings/create?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
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
        <span class="goal-form__label">Сумма цели</span>
        <input class="goal-form__input" type="number" name="target_amount" min="0.01" step="0.01" required value="<?= htmlspecialchars($formTargetAmount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      </label>

      <label class="goal-form__field">
        <span class="goal-form__label">Период</span>
        <select class="goal-form__input" name="period" required>
          <option value="week" <?= $formPeriod === 'week' ? 'selected' : '' ?>>Неделя</option>
          <option value="month" <?= $formPeriod === 'month' ? 'selected' : '' ?>>Месяц</option>
          <option value="year" <?= $formPeriod === 'year' ? 'selected' : '' ?>>Год</option>
        </select>
      </label>

      <label class="goal-form__field">
        <span class="goal-form__label">Дедлайн</span>
        <input class="goal-form__input" type="date" name="deadline" required value="<?= htmlspecialchars($formDeadline, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      </label>

      <button type="submit" class="transactions-submit">Сохранить цель</button>
    </form>
  </div>
</div>
