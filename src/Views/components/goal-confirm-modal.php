<?php

declare(strict_types=1);

$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$confirmDeleteAction = '/savings/delete?period=' . rawurlencode($selectedPeriod);
?>
<div
  class="goal-modal goal-confirm-modal"
  id="goal-confirm-modal"
  role="dialog"
  aria-modal="true"
  aria-labelledby="goal-confirm-title"
  aria-describedby="goal-confirm-description"
  hidden
>
  <div class="goal-modal__backdrop" data-goal-confirm-close></div>
  <div class="goal-modal__content goal-confirm-modal__content">
    <button type="button" class="goal-modal__close" data-goal-confirm-close aria-label="Закрыть модалку">×</button>
    <h2 class="goal-modal__title" id="goal-confirm-title">Подтвердите действие</h2>
    <p class="goal-confirm-modal__lead" id="goal-confirm-description"></p>
    <p class="goal-confirm-modal__amount" id="goal-confirm-amount-wrap">
      Накоплено: <span id="goal-confirm-amount"></span>
    </p>

    <form
      id="goal-confirm-form"
      method="POST"
      action="<?= htmlspecialchars($confirmDeleteAction, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
    >
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <input type="hidden" name="goal_id" id="goal-confirm-goal-id" value="">
      <input type="hidden" name="status" id="goal-confirm-status-input" value="cancelled">
      <input type="hidden" name="return_funds" id="goal-confirm-return-funds" value="0">

      <div class="goal-confirm-modal__buttons">
        <button
          id="goal-confirm-btn-return"
          class="goal-confirm-modal__btn goal-confirm-modal__btn--return"
          type="button"
        >
          Вернуть на баланс
        </button>
        <button
          id="goal-confirm-btn-keep"
          class="goal-confirm-modal__btn goal-confirm-modal__btn--keep"
          type="button"
        >
          Не возвращать
        </button>
      </div>
    </form>
  </div>
</div>
