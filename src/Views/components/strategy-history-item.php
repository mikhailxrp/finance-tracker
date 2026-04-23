<?php

declare(strict_types=1);

$historyItem = isset($historyItem) && is_array($historyItem) ? $historyItem : [];
$historyId = isset($historyId) && is_string($historyId) ? $historyId : uniqid('strategy-item-', true);

$createdAt = isset($historyItem['created_at']) ? (string) $historyItem['created_at'] : '';
$requestText = isset($historyItem['request_text']) ? (string) $historyItem['request_text'] : '';
$responseText = isset($historyItem['response_text']) ? (string) $historyItem['response_text'] : '';
$formattedDate = $createdAt;
$timestamp = strtotime($createdAt);
if ($timestamp !== false) {
  $formattedDate = date('d.m.Y', $timestamp);
}

$summaryLimit = 80;
$summary = mb_strlen($requestText) > $summaryLimit
  ? (mb_substr($requestText, 0, $summaryLimit) . '...')
  : $requestText;

$responseData = [];
$decodedResponse = json_decode($responseText, true);
if (is_array($decodedResponse)) {
  $responseData = isset($decodedResponse['response']) && is_array($decodedResponse['response'])
    ? $decodedResponse['response']
    : $decodedResponse;
}

$responseSummary = isset($responseData['summary']) ? (string) $responseData['summary'] : '';
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
?>
<article class="strategy-history-item" data-accordion-item>
  <button
    type="button"
    class="strategy-history-item__toggle"
    aria-expanded="false"
    aria-controls="<?= htmlspecialchars($historyId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
    data-accordion-toggle
  >
    <span class="strategy-history-item__meta">
      <time class="strategy-history-item__date"><?= htmlspecialchars($formattedDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></time>
      <span class="strategy-history-item__summary"><?= htmlspecialchars($summary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
    </span>
    <span class="strategy-history-item__chevron" aria-hidden="true">⌄</span>
  </button>

  <div id="<?= htmlspecialchars($historyId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="strategy-history-item__panel" hidden>
    <div class="strategy-history-item__question">
      <strong>Вопрос:</strong><br>
      <?= nl2br(htmlspecialchars($requestText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
    </div>

    <?php if ($responseSummary !== ''): ?>
      <div class="strategy-history-item__summary">
        <strong>Сводка:</strong><br>
        <?= nl2br(htmlspecialchars($responseSummary, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
      </div>
    <?php endif; ?>

    <?php if ($recommendations !== []): ?>
      <div class="strategy-history-item__recommendations">
        <strong>Рекомендации:</strong>
        <ul class="strategy-history-item__recommendation-list">
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
            <li class="strategy-history-item__recommendation strategy-history-item__recommendation--<?= htmlspecialchars($priority, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
              <?php if ($title !== ''): ?>
                <p class="strategy-history-item__recommendation-title">
                  <?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </p>
              <?php endif; ?>
              <?php if ($description !== ''): ?>
                <p class="strategy-history-item__recommendation-description">
                  <?= nl2br(htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
                </p>
              <?php endif; ?>
              <?php if ($impact !== ''): ?>
                <p class="strategy-history-item__recommendation-impact">
                  Эффект: <?= htmlspecialchars($impact, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </p>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($actionPlan !== []): ?>
      <div class="strategy-history-item__actions">
        <strong>План действий:</strong>
        <ol class="strategy-history-item__action-list">
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
      <div class="strategy-history-item__insights">
        <strong>Инсайты:</strong>
        <ul class="strategy-history-item__insight-list">
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
              <span class="strategy-history-item__insight-key"><?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:</span>
              <span class="strategy-history-item__insight-value">
                <?= htmlspecialchars($displayValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                <?= $key === 'savings_potential' ? ' ₽' : '' ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</article>
