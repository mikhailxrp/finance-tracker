<?php

declare(strict_types=1);

$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$editOldInput = isset($editOldInput) && is_array($editOldInput) ? $editOldInput : [];
?>
<div class="goal-modal" id="goal-edit-modal" role="dialog" aria-modal="true" aria-labelledby="goal-edit-modal-title" hidden>
  <div class="goal-modal__backdrop" data-goal-edit-close></div>
  <div class="goal-modal__content">
    <button type="button" class="goal-modal__close" data-goal-edit-close aria-label="Закрыть модалку">×</button>
    <h2 class="goal-modal__title" id="goal-edit-modal-title">Редактировать цель</h2>

    <form
      method="POST"
      action="/savings/update?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      class="goal-form"
      data-submit-loading
      data-loading-text="Сохранение..."
    >
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <input type="hidden" name="goal_id" id="goal-edit-id" value="<?= htmlspecialchars((string) ($editOldInput['goal_id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

      <label class="goal-form__field">
        <span class="goal-form__label">Название</span>
        <input class="goal-form__input" type="text" name="title" id="goal-edit-title" maxlength="150" required value="<?= htmlspecialchars((string) ($editOldInput['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      </label>

      <label class="goal-form__field">
        <span class="goal-form__label">Сумма цели</span>
        <input class="goal-form__input" type="number" name="target_amount" id="goal-edit-target-amount" min="0.01" step="0.01" required value="<?= htmlspecialchars((string) ($editOldInput['target_amount'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      </label>

      <label class="goal-form__field">
        <span class="goal-form__label">Период</span>
        <select class="goal-form__input" name="period" id="goal-edit-period" required>
          <option value="week" <?= (($editOldInput['period'] ?? '') === 'week') ? 'selected' : '' ?>>Неделя</option>
          <option value="month" <?= (($editOldInput['period'] ?? '') === 'month') ? 'selected' : '' ?>>Месяц</option>
          <option value="year" <?= (($editOldInput['period'] ?? '') === 'year') ? 'selected' : '' ?>>Год</option>
        </select>
      </label>

      <label class="goal-form__field">
        <span class="goal-form__label">Дедлайн</span>
        <input class="goal-form__input" type="date" name="deadline" id="goal-edit-deadline" required value="<?= htmlspecialchars((string) ($editOldInput['deadline'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      </label>

      <button type="submit" class="transactions-submit">Сохранить изменения</button>
    </form>
  </div>
</div>
