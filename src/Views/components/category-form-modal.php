<?php

declare(strict_types=1);

$csrf = isset($csrf) && is_string($csrf) ? $csrf : '';
?>
<div class="category-modal" data-category-modal hidden>
  <div class="category-modal__backdrop" data-category-modal-close></div>
  <div
    class="category-modal__dialog"
    role="dialog"
    aria-modal="true"
    aria-labelledby="category-modal-title"
  >
    <button
      type="button"
      class="category-modal__close"
      aria-label="Закрыть окно"
      data-category-modal-close
    >
      ×
    </button>

    <h3 id="category-modal-title" class="category-modal__title">Добавить категорию</h3>

    <form class="category-form" method="POST" action="/categories/create" data-category-form>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
      <input type="hidden" name="category_id" value="" data-category-id-input>

      <label class="profile-form__field">
        <span class="profile-form__label">Название</span>
        <input
          class="profile-form__input"
          type="text"
          name="name"
          maxlength="100"
          required
          data-category-name-input
        >
      </label>

      <fieldset class="category-form__type-group">
        <legend class="profile-form__label">Тип</legend>
        <label class="category-form__type-option">
          <input type="radio" name="type" value="income" checked data-category-type-input>
          <span>Доход</span>
        </label>
        <label class="category-form__type-option">
          <input type="radio" name="type" value="expense" data-category-type-input>
          <span>Расход</span>
        </label>
      </fieldset>

      <label class="profile-form__field">
        <span class="profile-form__label">Иконка (emoji)</span>
        <input
          class="profile-form__input"
          type="text"
          name="icon"
          maxlength="50"
          placeholder="Например: 💳"
          data-category-icon-input
        >
      </label>

      <label class="profile-form__field">
        <span class="profile-form__label">Цвет</span>
        <input
          class="profile-form__input profile-form__input--color"
          type="color"
          name="color"
          value="#6B7280"
          required
          data-category-color-input
        >
      </label>

      <button class="profile-form__submit" type="submit" data-category-submit-button>
        Создать категорию
      </button>
    </form>
  </div>
</div>
