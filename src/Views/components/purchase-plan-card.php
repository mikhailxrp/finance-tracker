<?php

declare(strict_types=1);

$plan = isset($plan) && is_array($plan) ? $plan : [];
$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$selectedPeriod = isset($selectedPeriod) && is_string($selectedPeriod) ? $selectedPeriod : 'month';

$planId = isset($plan['id']) ? (int) $plan['id'] : 0;
$title = isset($plan['title']) ? (string) $plan['title'] : '';
$targetFormatted = isset($plan['target_amount_formatted']) ? (string) $plan['target_amount_formatted'] : '0.00';
$termMonths = isset($plan['term_months']) ? (int) $plan['term_months'] : 0;
$strategies = isset($plan['strategies']) && is_array($plan['strategies']) ? $plan['strategies'] : [];
?>
<article class="purchase-plan-card" aria-labelledby="purchase-plan-title-<?= $planId > 0 ? $planId : '0' ?>">
  <div class="purchase-plan-card__head">
    <div class="purchase-plan-card__titles">
      <h3 class="purchase-plan-card__title" id="purchase-plan-title-<?= $planId > 0 ? $planId : '0' ?>">
        <?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
      </h3>
      <p class="purchase-plan-card__target">
        Цель:&nbsp;<?= htmlspecialchars($targetFormatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&nbsp;₽
        <?php if ($termMonths > 0): ?>
          <span class="purchase-plan-card__term">
            · Базовый срок:&nbsp;<?= htmlspecialchars((string) $termMonths, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&nbsp;мес.
          </span>
        <?php endif; ?>
      </p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-start">
      <form
        method="POST"
        action="/purchase-plans/convert?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        class="purchase-plan-card__convert-form"
        data-submit-loading
        data-loading-text="Добавление..."
      >
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="plan_id" value="<?= $planId > 0 ? (string) $planId : '' ?>">
        <button type="submit" class="btn btn-primary btn-sm">
          Добавить в цели
        </button>
      </form>
      <form
        method="POST"
        action="/purchase-plans/delete?period=<?= htmlspecialchars($selectedPeriod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        class="purchase-plan-card__delete-form"
        data-submit-loading
        data-loading-text="Удаление..."
      >
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="plan_id" value="<?= $planId > 0 ? (string) $planId : '' ?>">
        <button type="submit" class="purchase-plan-card__delete btn btn-outline-danger btn-sm">
          Удалить
        </button>
      </form>
    </div>
  </div>

  <div class="purchase-plan-card__strategies" role="group" aria-label="Стратегии накопления">
    <?php foreach ($strategies as $strategy): ?>
      <?php if (!is_array($strategy)): ?>
        <?php continue; ?>
      <?php endif; ?>
      <?php require __DIR__ . '/strategy-card.php'; ?>
    <?php endforeach; ?>
  </div>
</article>
