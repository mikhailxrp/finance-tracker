<?php

declare(strict_types=1);

$notice = isset($notice) && is_string($notice) ? $notice : null;
$error = isset($error) && is_string($error) ? $error : null;
$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$categories = isset($categories) && is_array($categories) ? $categories : [];
$transactions = isset($transactions) && is_array($transactions) ? $transactions : [];
$editingTransaction = isset($editingTransaction) && is_array($editingTransaction) ? $editingTransaction : null;

$isEditing = $editingTransaction !== null;
$editingId = $isEditing ? (int) ($editingTransaction['id'] ?? 0) : 0;
$formAction = $isEditing && $editingId > 0 ? '/transaction/' . $editingId . '/update' : '/transactions';
$formTitle = $isEditing ? 'Редактировать транзакцию' : 'Добавить транзакцию';
$submitLabel = $isEditing ? 'Сохранить' : '+ Добавить';
$submitLoadingText = $isEditing ? 'Сохранение изменений...' : 'Сохранение...';

$initialAmount = $isEditing ? (string) ($editingTransaction['amount'] ?? '') : '';
$initialCategoryId = $isEditing ? (int) ($editingTransaction['category_id'] ?? 0) : 0;
$initialDate = $isEditing ? (string) ($editingTransaction['date'] ?? '') : '';
$initialComment = $isEditing ? (string) ($editingTransaction['comment'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <title>Транзакции</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    >
    <link
      href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap"
      rel="stylesheet"
    >
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/typography.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="/assets/css/form-submit.css">
  </head>
  <body class="transactions-page">
    <?php require __DIR__ . '/components/header.php'; ?>

    <main class="transactions-main">
      <section class="transactions-card" aria-label="<?= $isEditing ? 'Редактирование транзакции' : 'Добавление транзакции' ?>">
        <h1 class="transactions-title"><?= htmlspecialchars($formTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>

        <?php if ($notice !== null && $notice !== ''): ?>
          <p class="transactions-alert transactions-alert--success" role="status">
            <?= htmlspecialchars($notice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
          </p>
        <?php endif; ?>

        <?php if ($error !== null && $error !== ''): ?>
          <p class="transactions-alert transactions-alert--error" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
          </p>
        <?php endif; ?>

        <form class="transactions-form" method="POST" action="<?= htmlspecialchars($formAction, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" data-submit-loading data-submit-overlay="true" data-loading-text="<?= htmlspecialchars($submitLoadingText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

          <label class="transactions-field">
            <span class="transactions-label">Сумма</span>
            <input class="transactions-input" type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" value="<?= htmlspecialchars($initialAmount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
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
                <?php $categoryType = isset($category['type']) ? (string) $category['type'] : ''; ?>
                <option value="<?= $categoryId ?>" <?= $initialCategoryId === $categoryId ? 'selected' : '' ?>>
                  <?= htmlspecialchars($categoryName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                  (<?= htmlspecialchars($categoryType, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="transactions-field">
            <span class="transactions-label">Дата</span>
            <input class="transactions-input" type="date" name="date" value="<?= htmlspecialchars($initialDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required>
          </label>

          <label class="transactions-field transactions-field--comment">
            <span class="transactions-label">Комментарий</span>
            <input class="transactions-input" type="text" name="comment" maxlength="255" value="<?= htmlspecialchars($initialComment, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Комментарий (опционально)">
          </label>

          <div class="transactions-form__actions">
            <button class="transactions-submit" type="submit" data-loading-text="<?= htmlspecialchars($submitLoadingText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
              <?= htmlspecialchars($submitLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </button>
            <?php if ($isEditing): ?>
              <a class="transactions-cancel" href="/transactions">Отмена</a>
            <?php endif; ?>
          </div>
        </form>
      </section>

      <section class="transactions-card" aria-label="Список транзакций">
        <div class="transactions-list__header">
          <h2 class="transactions-title transactions-title--sub">Последние транзакции</h2>
        </div>

        <?php if ($transactions === []): ?>
          <p class="transactions-empty">Транзакций пока нет. Добавьте первую запись выше.</p>
        <?php else: ?>
          <ul class="transactions-list">
            <?php foreach ($transactions as $item): ?>
              <?php if (!is_array($item)): ?>
                <?php continue; ?>
              <?php endif; ?>
              <?php $transaction = $item; ?>
              <?php require __DIR__ . '/components/transaction-row.php'; ?>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/header-dropdown.js"></script>
    <script type="module" src="/assets/js/form-submit.js"></script>
  </body>
</html>
