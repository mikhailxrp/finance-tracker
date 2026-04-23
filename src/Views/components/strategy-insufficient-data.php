<?php

declare(strict_types=1);

$insufficientData = isset($insufficientData) && is_array($insufficientData) ? $insufficientData : [];
$message = isset($insufficientData['message']) ? (string) $insufficientData['message'] : '';
$suggestions = isset($insufficientData['suggestions']) && is_array($insufficientData['suggestions'])
  ? $insufficientData['suggestions']
  : [];
$currentData = isset($insufficientData['current_data']) && is_array($insufficientData['current_data'])
  ? $insufficientData['current_data']
  : [];
$currentDataLabels = [
  'avg_monthly_income' => 'Средний доход в месяц',
  'avg_monthly_expense' => 'Средний расход в месяц',
  'total_income_3m' => 'Общий доход за 3 месяца',
  'total_expense_3m' => 'Общий расход за 3 месяца',
];

if ($message === '') {
  return;
}
?>
<section class="strategy-insufficient" role="alert" aria-label="Недостаточно данных для анализа">
  <div class="strategy-insufficient__header">
    <div class="strategy-insufficient__icon" aria-hidden="true">⚠</div>
    <div class="strategy-insufficient__header-content">
      <h3 class="strategy-insufficient__title">Недостаточно данных для анализа</h3>
      <p class="strategy-insufficient__message">
        <?= nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
      </p>
    </div>
  </div>

    <?php if ($suggestions !== []): ?>
      <div class="strategy-insufficient__suggestions">
        <strong>Что нужно добавить:</strong>
        <ul class="strategy-insufficient__list">
          <?php foreach ($suggestions as $item): ?>
            <?php if (!is_string($item) || trim($item) === ''): ?>
              <?php continue; ?>
            <?php endif; ?>
            <li><?= htmlspecialchars($item, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($currentData !== []): ?>
      <div class="strategy-insufficient__current-data">
        <strong>Текущие данные:</strong>
        <ul class="strategy-insufficient__list">
          <?php foreach ($currentData as $key => $value): ?>
            <?php if (!is_string($key)): ?>
              <?php continue; ?>
            <?php endif; ?>
            <?php
            $label = $currentDataLabels[$key] ?? $key;
            $numericValue = is_numeric($value) ? (float) $value : null;
            $displayValue = $numericValue !== null
              ? number_format($numericValue, 0, '.', ' ')
              : (string) $value;
            ?>
            <li>
              <?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:
              <?= htmlspecialchars($displayValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
              <?= $numericValue !== null ? ' ₽' : '' ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
</section>
