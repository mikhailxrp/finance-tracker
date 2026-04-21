<?php

declare(strict_types=1);

$transactionId = isset($transaction['id']) ? (int) $transaction['id'] : 0;
$amountValue = isset($transaction['amount']) ? (float) $transaction['amount'] : 0.0;
$dateValue = isset($transaction['date']) ? (string) $transaction['date'] : '';
$commentValue = isset($transaction['comment']) ? (string) $transaction['comment'] : '';
$categoryName = isset($transaction['category_name']) ? (string) $transaction['category_name'] : 'Без категории';
$categoryType = isset($transaction['category_type']) ? (string) $transaction['category_type'] : 'expense';
$categoryIcon = isset($transaction['category_icon']) ? (string) $transaction['category_icon'] : '•';
$currentPath = isset($currentPath) && is_string($currentPath) ? $currentPath : '/income';
$transactionRowVariant = isset($transactionRowVariant) && is_string($transactionRowVariant) ? $transactionRowVariant : 'default';
$isCompactRow = $transactionRowVariant === 'compact';

$parsedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $dateValue);
$displayDate = $parsedDate instanceof \DateTimeImmutable ? $parsedDate->format('d.m.Y') : $dateValue;

$safeDate = htmlspecialchars($displayDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeComment = htmlspecialchars($commentValue !== '' ? $commentValue : 'Без комментария', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeCategoryName = htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeCategoryIcon = htmlspecialchars($categoryIcon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$amountSign = $categoryType === 'income' ? '+' : '-';
$amountClass = $categoryType === 'income'
  ? 'transactions-item__amount transactions-item__amount--income'
  : 'transactions-item__amount transactions-item__amount--expense';
$chipClass = $categoryType === 'income'
  ? 'transactions-item__chip transactions-item__chip--income'
  : 'transactions-item__chip transactions-item__chip--expense';
$amountDecimals = $isCompactRow ? 0 : 2;
$amountText = $amountSign . number_format($amountValue, $amountDecimals, '.', ' ') . ' ₽';
$deleteModalId = 'deleteTransactionModal' . $transactionId;
$safeReturnPath = htmlspecialchars($currentPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$itemClass = $isCompactRow ? 'transactions-item transactions-item--compact' : 'transactions-item';

if ($transactionId <= 0) {
  return;
}
?>
<li class="<?= $itemClass ?>" data-transaction-id="<?= $transactionId ?>">
  <span class="transactions-item__date"><?= $safeDate ?></span>
  <span class="transactions-item__category">
    <span class="<?= $chipClass ?>">
      <span class="transactions-item__icon"><?= $safeCategoryIcon ?></span>
      <span class="transactions-item__name"><?= $safeCategoryName ?></span>
    </span>
  </span>
  <span class="transactions-item__comment"><?= $safeComment ?></span>
  <span class="<?= $amountClass ?>"><?= htmlspecialchars($amountText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
  <span class="transactions-item__actions">
    <a
      class="transactions-item__action transactions-item__action--edit"
      href="/transaction/<?= $transactionId ?>/edit?return=<?= $safeReturnPath ?>"
      aria-label="Редактировать транзакцию <?= $transactionId ?>"
      title="Редактировать"
    >
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </a>
    <button
      type="button"
      class="transactions-item__action transactions-item__action--delete"
      data-bs-toggle="modal"
      data-bs-target="#<?= htmlspecialchars($deleteModalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      aria-label="Удалить транзакцию <?= $transactionId ?>"
      title="Удалить"
    >
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </span>

  <?php require __DIR__ . '/confirm-modal.php'; ?>
</li>
