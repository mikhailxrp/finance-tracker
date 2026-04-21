<?php

declare(strict_types=1);

$goal = isset($goal) && is_array($goal) ? $goal : [];
$goalTitle = isset($goal['title']) ? (string) $goal['title'] : 'Без названия';
$targetAmount = isset($goal['target_amount']) ? (string) $goal['target_amount'] : '0.00';
$currentAmount = isset($goal['current_amount']) ? (string) $goal['current_amount'] : '0.00';
$progressPercent = isset($goal['progress_percent']) ? (float) $goal['progress_percent'] : 0.0;
$goalId = isset($goal['id']) ? (int) $goal['id'] : 0;
$daysLeftText = isset($goal['days_left_text']) ? (string) $goal['days_left_text'] : 'Срок истёк';
$status = isset($goal['status']) ? (string) $goal['status'] : 'active';
$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$editError = isset($editError) && is_string($editError) ? $editError : null;
$contributionError = isset($contributionError) && is_string($contributionError) ? $contributionError : null;
$editOldInput = isset($editOldInput) && is_array($editOldInput) ? $editOldInput : [];
$contributeOldInput = isset($contributeOldInput) && is_array($contributeOldInput) ? $contributeOldInput : [];
$isCompleted = $status === 'completed';
$isCancelled = $status === 'cancelled';
$cardClass = 'goal-card';
if ($isCompleted) {
  $cardClass .= ' goal-card--completed';
}
if ($isCancelled) {
  $cardClass .= ' goal-card--cancelled';
}
$statusButtonLabel = $isCancelled ? 'Восстановить' : 'Отменить';
$statusValue = $isCancelled ? 'active' : 'cancelled';
$displayAmountFormatted = number_format((float) $currentAmount, 2, '.', ' ');
$contributionValue = isset($contributeOldInput['goal_id'], $contributeOldInput['amount'])
  && (int) $contributeOldInput['goal_id'] === $goalId
  ? (string) $contributeOldInput['amount']
  : '';
?>
<article class="<?= htmlspecialchars($cardClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="Цель: <?= htmlspecialchars($goalTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <header class="goal-card__header">
    <span class="goal-card__icon" aria-hidden="true">🎯</span>
    <h2 class="goal-card__title"><?= htmlspecialchars($goalTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
    <?php if ($isCompleted): ?>
      <span class="goal-card__status goal-card__status--completed" aria-label="Статус: выполнена">✅</span>
    <?php elseif ($isCancelled): ?>
      <span class="goal-card__status goal-card__status--cancelled" aria-label="Статус: отменена">⛔</span>
    <?php endif; ?>
  </header>

  <div class="goal-card__progress-wrap" role="group" aria-label="Прогресс цели">
    <progress
      class="goal-card__progress-track"
      max="100"
      value="<?= htmlspecialchars((string) $progressPercent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      aria-label="Прогресс в процентах"></progress>
    <p class="goal-card__progress-value"><?= htmlspecialchars((string) number_format($progressPercent, 0, '.', ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>%</p>
  </div>

  <p class="goal-card__amounts">
    Накоплено <?= htmlspecialchars((string) number_format((float) $currentAmount, 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    /
    Цель <?= htmlspecialchars((string) number_format((float) $targetAmount, 2, '.', ' '), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
  </p>
  <p class="goal-card__deadline"><?= htmlspecialchars($daysLeftText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>

  <?php if ($contributionError !== null && isset($contributeOldInput['goal_id']) && (int) $contributeOldInput['goal_id'] === $goalId): ?>
    <p class="goal-card__error" role="alert"><?= htmlspecialchars($contributionError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if (!$isCompleted && !$isCancelled): ?>
    <form
      method="POST"
      action="/savings/contribute?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      class="goal-card__contribute"
      data-submit-loading
      data-loading-text="Списание...">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <input type="hidden" name="goal_id" value="<?= htmlspecialchars((string) $goalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <label class="goal-card__contribute-label" for="goal-contribute-<?= htmlspecialchars((string) $goalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        Сумма пополнения
      </label>
      <input
        id="goal-contribute-<?= htmlspecialchars((string) $goalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        class="goal-card__contribute-input"
        type="number"
        name="amount"
        min="0.01"
        step="0.01"
        required
        value="<?= htmlspecialchars($contributionValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <button class="goal-card__contribute-submit" type="submit">Списать с баланса</button>
    </form>
  <?php endif; ?>

  <div class="goal-card__actions">
    <button
      type="button"
      class="goal-card__action"
      data-goal-edit-open
      data-goal-id="<?= htmlspecialchars((string) $goalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      data-goal-title="<?= htmlspecialchars($goalTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      data-goal-target="<?= htmlspecialchars((string) $targetAmount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      data-goal-period="<?= htmlspecialchars((string) ($goal['period'] ?? 'month'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      data-goal-deadline="<?= htmlspecialchars((string) ($goal['deadline'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      aria-haspopup="dialog"
      aria-controls="goal-edit-modal">Редактировать</button>

    <?php if ($isCancelled): ?>
      <form method="POST" action="/savings/status?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="goal_id" value="<?= htmlspecialchars((string) $goalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <button class="goal-card__action" type="submit"><?= htmlspecialchars($statusButtonLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></button>
      </form>
    <?php else: ?>
      <button
        type="button"
        class="goal-card__action"
        data-goal-confirm-open
        data-goal-confirm-action="cancel"
        data-goal-id="<?= htmlspecialchars((string) $goalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        data-goal-title="<?= htmlspecialchars($goalTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        data-goal-amount="<?= htmlspecialchars($displayAmountFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        aria-haspopup="dialog"
        aria-controls="goal-confirm-modal"><?= htmlspecialchars($statusButtonLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></button>
    <?php endif; ?>

    <?php if ($isCancelled): ?>
      <form method="POST" action="/savings/delete?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="goal_id" value="<?= htmlspecialchars((string) $goalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="return_funds" value="0">
        <button class="goal-card__action goal-card__action--danger" type="submit">Удалить</button>
      </form>
    <?php else: ?>
      <button
        type="button"
        class="goal-card__action goal-card__action--danger"
        data-goal-confirm-open
        data-goal-confirm-action="delete"
        data-goal-id="<?= htmlspecialchars((string) $goalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        data-goal-title="<?= htmlspecialchars($goalTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        data-goal-amount="<?= htmlspecialchars($displayAmountFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        aria-haspopup="dialog"
        aria-controls="goal-confirm-modal">Удалить</button>
    <?php endif; ?>
  </div>

  <?php if ($editError !== null && isset($editOldInput['goal_id']) && (int) $editOldInput['goal_id'] === $goalId): ?>
    <p class="goal-card__error" role="alert"><?= htmlspecialchars($editError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php endif; ?>
</article>