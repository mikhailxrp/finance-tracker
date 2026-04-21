<?php

declare(strict_types=1);

$strategy = isset($strategy) && is_array($strategy) ? $strategy : [];
$label = isset($strategy['label']) ? (string) $strategy['label'] : '';
$emoji = isset($strategy['emoji']) ? (string) $strategy['emoji'] : '';
$months = isset($strategy['months']) ? (int) $strategy['months'] : 0;
$monthlyFormatted = isset($strategy['monthly_payment_formatted'])
  ? (string) $strategy['monthly_payment_formatted']
  : '0.00';
$overpaymentFormatted = isset($strategy['overpayment_formatted'])
  ? (string) $strategy['overpayment_formatted']
  : '0.00';
$description = isset($strategy['description']) ? (string) $strategy['description'] : '';
$paymentDtLabel = isset($strategy['payment_dt_label']) && (string) $strategy['payment_dt_label'] !== ''
  ? (string) $strategy['payment_dt_label']
  : 'Месячный платёж';
$thirdDtLabel = isset($strategy['third_dt_label']) && (string) $strategy['third_dt_label'] !== ''
  ? (string) $strategy['third_dt_label']
  : 'Переплата';
$thirdDdValue = isset($strategy['third_dd_value']) ? (string) $strategy['third_dd_value'] : null;
$thirdDdPlain = isset($strategy['third_dd_plain']) && $strategy['third_dd_plain'] === true;
$thirdDisplay = $thirdDdValue !== null && $thirdDdValue !== '' ? $thirdDdValue : $overpaymentFormatted;
?>
<div class="strategy-card" role="group" aria-label="Сценарий: <?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <div class="strategy-card__head">
    <span class="strategy-card__emoji" aria-hidden="true"><?= htmlspecialchars($emoji, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
    <div class="strategy-card__titles">
      <p class="strategy-card__label"><?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
      <?php if ($description !== ''): ?>
        <p class="strategy-card__hint"><?= htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
      <?php endif; ?>
    </div>
  </div>
  <dl class="strategy-card__stats">
    <div class="strategy-card__stat">
      <dt class="strategy-card__dt"><?= htmlspecialchars($paymentDtLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dt>
      <dd class="strategy-card__dd"><?= htmlspecialchars($monthlyFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&nbsp;₽</dd>
    </div>
    <div class="strategy-card__stat">
      <dt class="strategy-card__dt">Срок</dt>
      <dd class="strategy-card__dd"><?= htmlspecialchars((string) $months, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&nbsp;мес.</dd>
    </div>
    <div class="strategy-card__stat">
      <dt class="strategy-card__dt"><?= htmlspecialchars($thirdDtLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></dt>
      <dd class="strategy-card__dd"><?= htmlspecialchars($thirdDisplay, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?><?= $thirdDdPlain ? '' : '&nbsp;₽' ?></dd>
    </div>
  </dl>
</div>
