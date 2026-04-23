<?php

declare(strict_types=1);

$latestStrategy = isset($latestStrategy) && is_array($latestStrategy) ? $latestStrategy : [];
$requestData = isset($latestStrategy['request_data']) && is_array($latestStrategy['request_data'])
  ? $latestStrategy['request_data']
  : [];
$message = isset($requestData['message']) ? (string) $requestData['message'] : '';
$responseText = isset($latestStrategy['response_text']) ? (string) $latestStrategy['response_text'] : '';
$responseData = [];
$decodedResponse = json_decode($responseText, true);
if (is_array($decodedResponse)) {
  $responseData = isset($decodedResponse['response']) && is_array($decodedResponse['response'])
    ? $decodedResponse['response']
    : $decodedResponse;
}

$summary = isset($responseData['summary']) ? (string) $responseData['summary'] : '';
$recommendations = isset($responseData['recommendations']) && is_array($responseData['recommendations'])
  ? $responseData['recommendations']
  : [];
$actionPlan = isset($responseData['action_plan']) && is_array($responseData['action_plan'])
  ? $responseData['action_plan']
  : [];
$insights = isset($responseData['insights']) && is_array($responseData['insights'])
  ? $responseData['insights']
  : [];
$insightLabels = [
  'income_trend' => 'Тренд доходов',
  'expense_trend' => 'Тренд расходов',
  'savings_potential' => 'Потенциал накоплений',
];

if ($message === '' || $summary === '') {
  return;
}
?>
<section class="strategy-result" aria-label="Последний ответ AI">
  <h2 class="strategy-result__title">Последняя стратегия</h2>
  <div class="strategy-result__question">
    <strong>Ваш вопрос:</strong><br>
    <?= nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
  </div>
  <div class="strategy-result__summary">
    <strong>Сводка:</strong><br>
    <?= nl2br(htmlspecialchars($summary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
  </div>

  <?php if ($recommendations !== []): ?>
    <div class="strategy-result__recommendations">
      <strong>Рекомендации:</strong>
      <ul class="strategy-result__recommendation-list">
        <?php foreach ($recommendations as $recommendation): ?>
          <?php if (!is_array($recommendation)): ?>
            <?php continue; ?>
          <?php endif; ?>
          <?php
          $priority = isset($recommendation['priority']) ? (string) $recommendation['priority'] : 'medium';
          $title = isset($recommendation['title']) ? (string) $recommendation['title'] : '';
          $description = isset($recommendation['description']) ? (string) $recommendation['description'] : '';
          $impact = isset($recommendation['impact']) ? (string) $recommendation['impact'] : '';
          ?>
          <li class="strategy-result__recommendation strategy-result__recommendation--<?= htmlspecialchars($priority, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php if ($title !== ''): ?>
              <p class="strategy-result__recommendation-title">
                <?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
              </p>
            <?php endif; ?>
            <?php if ($description !== ''): ?>
              <p class="strategy-result__recommendation-description">
                <?= nl2br(htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
              </p>
            <?php endif; ?>
            <?php if ($impact !== ''): ?>
              <p class="strategy-result__recommendation-impact">
                Эффект: <?= htmlspecialchars($impact, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
              </p>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($actionPlan !== []): ?>
    <div class="strategy-result__actions">
      <strong>План действий:</strong>
      <ol class="strategy-result__action-list">
        <?php foreach ($actionPlan as $actionItem): ?>
          <?php if (!is_string($actionItem) || trim($actionItem) === ''): ?>
            <?php continue; ?>
          <?php endif; ?>
          <li><?= htmlspecialchars($actionItem, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
  <?php endif; ?>

  <?php if ($insights !== []): ?>
    <div class="strategy-result__insights">
      <strong>Инсайты:</strong>
      <ul class="strategy-result__insight-list">
        <?php foreach ($insights as $key => $value): ?>
          <?php if (!is_string($key)): ?>
            <?php continue; ?>
          <?php endif; ?>
          <?php
          $label = $insightLabels[$key] ?? $key;
          $displayValue = $key === 'savings_potential'
            ? number_format((float) $value, 0, '.', ' ')
            : (string) $value;
          ?>
          <li>
            <span class="strategy-result__insight-key"><?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:</span>
            <span class="strategy-result__insight-value">
              <?= htmlspecialchars($displayValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
              <?= $key === 'savings_potential' ? ' ₽' : '' ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</section>
