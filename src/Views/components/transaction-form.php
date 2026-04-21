<?php

declare(strict_types=1);

$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$categories = isset($categories) && is_array($categories) ? $categories : [];
$editingTransaction = isset($editingTransaction) && is_array($editingTransaction) ? $editingTransaction : null;
$currentPath = isset($currentPath) && is_string($currentPath) ? $currentPath : '/income';
$transactionFormVariant = isset($transactionFormVariant) && is_string($transactionFormVariant) ? $transactionFormVariant : '';
$createAction = isset($formAction) && is_string($formAction) ? $formAction : $currentPath;
$defaultFormTitle = isset($formTitle) && is_string($formTitle) && $formTitle !== '' ? $formTitle : 'Добавить транзакцию';

$isEditing = $editingTransaction !== null;
$editingId = $isEditing ? (int) ($editingTransaction['id'] ?? 0) : 0;
$action = $isEditing && $editingId > 0 ? '/transaction/' . $editingId . '/update' : $createAction;
$title = $isEditing ? 'Редактировать транзакцию' : $defaultFormTitle;
$submitLabel = $isEditing ? 'Сохранить' : '+ Добавить';
$submitLoadingText = $isEditing ? 'Сохранение изменений...' : 'Сохранение...';

$initialAmount = $isEditing ? (string) ($editingTransaction['amount'] ?? '') : '';
$initialCategoryId = $isEditing ? (int) ($editingTransaction['category_id'] ?? 0) : 0;
$initialDate = $isEditing ? (string) ($editingTransaction['date'] ?? '') : '';
$initialComment = $isEditing ? (string) ($editingTransaction['comment'] ?? '') : '';
$sectionClass = $transactionFormVariant !== '' ? 'transactions-card transactions-card--' . $transactionFormVariant : 'transactions-card';
$titleClass = $transactionFormVariant !== '' ? 'transactions-title transactions-title--' . $transactionFormVariant : 'transactions-title';
?>
<section class="<?= htmlspecialchars($sectionClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
  <h1 class="<?= htmlspecialchars($titleClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>

  <form
    class="transactions-form"
    method="POST"
    action="<?= htmlspecialchars($action, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
    data-submit-loading
    data-submit-overlay="true"
    data-loading-text="<?= htmlspecialchars($submitLoadingText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
  >
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <input type="hidden" name="return_to" value="<?= htmlspecialchars($currentPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

    <label class="transactions-field">
      <span class="transactions-label">Сумма</span>
      <input
        class="transactions-input"
        type="number"
        name="amount"
        step="0.01"
        min="0.01"
        placeholder="0.00"
        value="<?= htmlspecialchars($initialAmount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        required
      >
    </label>

    <label class="transactions-field">
      <span class="transactions-label">Категория</span>
      <select class="transactions-input" name="category_id" required>
        <option value="">Выберите категорию</option>
        <?php foreach ($categories as $category): ?>
          <?php if (!is_array($category)): ?>
            <?php continue; ?>
          <?php endif; ?>
          <?php $categoryId = isset($category['id']) ? (int) $category['id'] : 0; ?>
          <?php if ($categoryId <= 0): ?>
            <?php continue; ?>
          <?php endif; ?>
          <?php $categoryName = isset($category['name']) ? (string) $category['name'] : ''; ?>
          <option value="<?= $categoryId ?>" <?= $initialCategoryId === $categoryId ? 'selected' : '' ?>>
            <?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="transactions-field">
      <span class="transactions-label">Дата</span>
      <input
        class="transactions-input"
        type="date"
        name="date"
        value="<?= htmlspecialchars($initialDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        required
      >
    </label>

    <label class="transactions-field transactions-field--comment">
      <span class="transactions-label">Комментарий</span>
      <input
        class="transactions-input"
        type="text"
        name="comment"
        maxlength="255"
        value="<?= htmlspecialchars($initialComment, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        placeholder="Комментарий (опционально)"
      >
    </label>

    <div class="transactions-form__actions">
      <button class="transactions-submit" type="submit" data-loading-text="<?= htmlspecialchars($submitLoadingText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <?= htmlspecialchars($submitLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
      </button>
      <?php if ($isEditing): ?>
        <a class="transactions-cancel" href="<?= htmlspecialchars($currentPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Отмена</a>
      <?php endif; ?>
    </div>
  </form>
</section>
