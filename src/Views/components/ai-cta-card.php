<?php

declare(strict_types=1);

$strategyPresets = isset($strategyPresets) && is_array($strategyPresets) ? $strategyPresets : [];
$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$oldInput = isset($oldInput) && is_array($oldInput) ? $oldInput : [];
$messageValue = isset($oldInput['message']) && is_string($oldInput['message']) ? $oldInput['message'] : '';
?>
<section class="strategy-cta" aria-label="Запрос AI-стратегии">
  <div class="strategy-cta__icon" aria-hidden="true">AI</div>
  <div class="strategy-cta__head">
    <h1 class="strategy-cta__title">Персональная стратегия</h1>
    <p class="strategy-cta__subtitle">Выберите пресет или опишите запрос своими словами.</p>
  </div>

  <div class="strategy-cta__presets" role="group" aria-label="Быстрые пресеты">
    <?php foreach ($strategyPresets as $preset): ?>
      <?php if (!is_string($preset) || trim($preset) === ''): ?>
        <?php continue; ?>
      <?php endif; ?>
      <button
        type="button"
        class="strategy-preset"
        data-strategy-preset="<?= htmlspecialchars($preset, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      >
        <?= htmlspecialchars($preset, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
      </button>
    <?php endforeach; ?>
  </div>

  <form class="strategy-cta__form" method="post" action="/strategy/generate" data-strategy-form>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <label class="strategy-cta__field" for="strategy-request">
      <span class="strategy-cta__label">Ваш запрос</span>
      <textarea
        id="strategy-request"
        class="strategy-cta__textarea"
        name="message"
        rows="4"
        maxlength="2000"
        placeholder="Например: разберите мой бюджет за месяц и предложите план экономии на 3 шага."
      ><?= htmlspecialchars($messageValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
    </label>

    <button type="submit" class="strategy-cta__submit" id="generate-btn">Получить стратегию</button>
  </form>
</section>
