<?php

declare(strict_types=1);

$credit = isset($credit) && is_array($credit) ? $credit : [];
$creditTitle = isset($credit['title']) ? (string) $credit['title'] : 'Без названия';
$creditId = isset($credit['id']) ? (int) $credit['id'] : 0;
$totalFormatted = isset($credit['total_amount_formatted']) ? (string) $credit['total_amount_formatted'] : '0.00';
$rateFormatted = isset($credit['interest_rate_formatted']) ? (string) $credit['interest_rate_formatted'] : '0.00';
$startDate = isset($credit['start_date']) ? (string) $credit['start_date'] : '';
$status = isset($credit['status']) ? (string) $credit['status'] : 'active';
$strategies = isset($credit['strategies']) && is_array($credit['strategies']) ? $credit['strategies'] : [];
$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';
$editError = isset($editError) && is_string($editError) ? $editError : null;
$editOldInput = isset($editOldInput) && is_array($editOldInput) ? $editOldInput : [];
$isActive = $status === 'active';

$cardModifier = $isActive ? '' : ' credit-card--closed';
?>
<article class="credit-card<?= htmlspecialchars($cardModifier, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="Кредит: <?= htmlspecialchars($creditTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <header class="credit-card__header">
    <span class="credit-card__icon" aria-hidden="true">💳</span>
    <div class="credit-card__head-text">
      <h2 class="credit-card__title"><?= htmlspecialchars($creditTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
      <?php if (!$isActive): ?>
        <span class="credit-card__badge" role="status">Закрыт</span>
      <?php endif; ?>
    </div>
  </header>

  <dl class="credit-card__meta">
    <div class="credit-card__meta-row">
      <dt>Сумма</dt>
      <dd><?= htmlspecialchars($totalFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&nbsp;₽</dd>
    </div>
    <div class="credit-card__meta-row">
      <dt>Ставка</dt>
      <dd><?= htmlspecialchars($rateFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>% годовых</dd>
    </div>
    <div class="credit-card__meta-row">
      <dt>Дата начала</dt>
      <dd><?= htmlspecialchars($startDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dd>
    </div>
  </dl>

  <?php if ($isActive && count($strategies) > 0): ?>
    <section class="credit-card__strategies" aria-label="Сценарии погашения">
      <h3 class="credit-card__strategies-title">Варианты погашения</h3>
      <div class="credit-card__strategies-list">
        <?php foreach ($strategies as $strategy): ?>
          <?php if (!is_array($strategy)): ?>
            <?php continue; ?>
          <?php endif; ?>
          <?php require __DIR__ . '/strategy-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <div class="credit-card__actions">
    <button
      type="button"
      class="credit-card__action"
      data-credit-edit-open
      data-credit-id="<?= htmlspecialchars((string) $creditId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      data-credit-title="<?= htmlspecialchars($creditTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      data-credit-total="<?= htmlspecialchars((string) ($credit['total_amount'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      data-credit-rate="<?= htmlspecialchars((string) ($credit['interest_rate'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      data-credit-start="<?= htmlspecialchars($startDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      aria-haspopup="dialog"
      aria-controls="credit-edit-modal"
    >Редактировать</button>

    <?php if ($isActive): ?>
      <form
        method="POST"
        action="/credits/close?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        class="credit-card__action-form"
        data-submit-loading
        data-loading-text="Закрытие..."
      >
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="credit_id" value="<?= htmlspecialchars((string) $creditId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <button class="credit-card__action credit-card__action--secondary" type="submit">Закрыть</button>
      </form>
    <?php endif; ?>

    <form
      method="POST"
      action="/credits/delete?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      class="credit-card__action-form"
      data-submit-loading
      data-loading-text="Удаление..."
    >
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <input type="hidden" name="credit_id" value="<?= htmlspecialchars((string) $creditId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <button class="credit-card__action credit-card__action--danger" type="submit">Удалить</button>
    </form>
  </div>

  <?php if ($editError !== null && isset($editOldInput['credit_id']) && (int) $editOldInput['credit_id'] === $creditId): ?>
    <p class="credit-card__error" role="alert"><?= htmlspecialchars($editError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
  <?php endif; ?>
</article>
