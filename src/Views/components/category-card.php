<?php

declare(strict_types=1);

$category = isset($category) && is_array($category) ? $category : [];
$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
$isSystem = (bool) ($category['is_system'] ?? false);
$categoryId = isset($category['id']) ? (int) $category['id'] : 0;
$name = isset($category['name']) ? (string) $category['name'] : '';
$type = isset($category['type']) ? (string) $category['type'] : '';
$icon = isset($category['icon']) ? trim((string) $category['icon']) : '';
$color = isset($category['color']) ? (string) $category['color'] : '#6B7280';

if ($categoryId <= 0 || $name === '' || ($type !== 'income' && $type !== 'expense')) {
  return;
}
?>
<article class="category-card<?= $isSystem ? ' category-card--system' : '' ?>">
  <div class="category-card__head">
    <span class="category-card__icon" aria-hidden="true">
      <?= htmlspecialchars($icon !== '' ? $icon : '•', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </span>
    <div class="category-card__meta">
      <h3 class="category-card__name"><?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
      <p class="category-card__type">
        <?= $type === 'income' ? 'Доход' : 'Расход' ?>
      </p>
    </div>
    <span
      class="category-card__color"
      data-category-color-swatch
      data-category-color="<?= htmlspecialchars($color, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      aria-hidden="true"
    ></span>
  </div>

  <div class="category-card__actions">
    <?php if ($isSystem): ?>
      <span class="category-card__badge">Системная</span>
    <?php else: ?>
      <button
        type="button"
        class="category-btn category-btn--edit"
        data-category-edit
        data-category-id="<?= htmlspecialchars((string) $categoryId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        data-category-name="<?= htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        data-category-type="<?= htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        data-category-icon="<?= htmlspecialchars($icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
        data-category-color="<?= htmlspecialchars($color, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
      >
        Редактировать
      </button>
      <form method="POST" action="/categories/delete" data-category-delete-form>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <input type="hidden" name="category_id" value="<?= htmlspecialchars((string) $categoryId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <button type="submit" class="category-btn category-btn--delete">Удалить</button>
      </form>
    <?php endif; ?>
  </div>
</article>
