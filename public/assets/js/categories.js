/**
 * CRUD-модалка категорий на странице профиля.
 */
(function () {
  'use strict';

  const modal = document.querySelector('[data-category-modal]');
  if (!modal) {
    return;
  }

  const form = modal.querySelector('[data-category-form]');
  const title = modal.querySelector('#category-modal-title');
  const submitButton = modal.querySelector('[data-category-submit-button]');
  const idInput = modal.querySelector('[data-category-id-input]');
  const nameInput = modal.querySelector('[data-category-name-input]');
  const iconInput = modal.querySelector('[data-category-icon-input]');
  const colorInput = modal.querySelector('[data-category-color-input]');
  const typeInputs = modal.querySelectorAll('[data-category-type-input]');
  const openButton = document.querySelector('[data-category-open]');
  const closeButtons = modal.querySelectorAll('[data-category-modal-close]');
  const editButtons = document.querySelectorAll('[data-category-edit]');
  const deleteForms = document.querySelectorAll('[data-category-delete-form]');
  const swatches = document.querySelectorAll('[data-category-color-swatch]');

  const CREATE_ACTION = '/categories/create';
  const UPDATE_ACTION = '/categories/update';
  const DEFAULT_COLOR = '#6B7280';

  if (!form || !title || !submitButton || !idInput || !nameInput || !iconInput || !colorInput || typeInputs.length === 0) {
    return;
  }

  const setTypeValue = (nextType, lockType) => {
    typeInputs.forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }

      input.checked = input.value === nextType;
      input.disabled = lockType;
    });
  };

  const resetFormToCreate = () => {
    form.setAttribute('action', CREATE_ACTION);
    title.textContent = 'Добавить категорию';
    submitButton.textContent = 'Создать категорию';
    idInput.value = '';
    nameInput.value = '';
    iconInput.value = '';
    colorInput.value = DEFAULT_COLOR;
    setTypeValue('income', false);
  };

  const openModal = () => {
    modal.hidden = false;
    document.body.classList.add('modal-open');
    window.setTimeout(() => {
      nameInput.focus();
    }, 0);
  };

  const closeModal = () => {
    modal.hidden = true;
    document.body.classList.remove('modal-open');
  };

  const fillFormForEdit = (button) => {
    form.setAttribute('action', UPDATE_ACTION);
    title.textContent = 'Редактировать категорию';
    submitButton.textContent = 'Сохранить изменения';

    idInput.value = button.getAttribute('data-category-id') || '';
    nameInput.value = button.getAttribute('data-category-name') || '';
    iconInput.value = button.getAttribute('data-category-icon') || '';
    colorInput.value = button.getAttribute('data-category-color') || DEFAULT_COLOR;

    const categoryType = button.getAttribute('data-category-type') || 'income';
    setTypeValue(categoryType, true);
  };

  swatches.forEach((swatch) => {
    const color = swatch.getAttribute('data-category-color');
    if (color) {
      swatch.style.backgroundColor = color;
    }
  });

  if (openButton) {
    openButton.addEventListener('click', () => {
      resetFormToCreate();
      openModal();
    });
  }

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });

  editButtons.forEach((button) => {
    button.addEventListener('click', () => {
      fillFormForEdit(button);
      openModal();
    });
  });

  deleteForms.forEach((deleteForm) => {
    deleteForm.addEventListener('submit', (event) => {
      const ok = window.confirm('Удалить категорию?');
      if (!ok) {
        event.preventDefault();
      }
    });
  });
})();
