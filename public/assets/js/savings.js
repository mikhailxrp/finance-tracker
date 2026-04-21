const MODAL_OPEN_SELECTOR = '[data-goal-modal-open]';
const MODAL_CLOSE_SELECTOR = '[data-goal-modal-close]';
const MODAL_ID = 'goal-create-modal';
const EDIT_MODAL_OPEN_SELECTOR = '[data-goal-edit-open]';
const EDIT_MODAL_CLOSE_SELECTOR = '[data-goal-edit-close]';
const EDIT_MODAL_ID = 'goal-edit-modal';
const CONFIRM_MODAL_ID = 'goal-confirm-modal';
const CONFIRM_OPEN_SELECTOR = '[data-goal-confirm-open]';
const CONFIRM_CLOSE_SELECTOR = '[data-goal-confirm-close]';
const ESCAPE_KEY = 'Escape';
const FOCUSABLE_SELECTOR =
  'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

const getModalElements = () => {
  const modal = document.getElementById(MODAL_ID);
  if (!(modal instanceof HTMLElement)) {
    return null;
  }

  const focusable = modal.querySelectorAll(FOCUSABLE_SELECTOR);
  const firstFocusable = focusable.length > 0 ? focusable[0] : null;

  return {
    modal,
    firstFocusable: firstFocusable instanceof HTMLElement ? firstFocusable : null,
  };
};

const openModal = () => {
  const elements = getModalElements();
  if (elements === null) {
    return;
  }

  elements.modal.hidden = false;
  document.body.classList.add('modal-open');
  if (elements.firstFocusable !== null) {
    elements.firstFocusable.focus();
  }
};

const closeModal = () => {
  const elements = getModalElements();
  if (elements === null) {
    return;
  }

  elements.modal.hidden = true;
  document.body.classList.remove('modal-open');
};

const getEditModal = () => {
  const modal = document.getElementById(EDIT_MODAL_ID);
  return modal instanceof HTMLElement ? modal : null;
};

const setEditFormValues = (trigger) => {
  const idField = document.getElementById('goal-edit-id');
  const titleField = document.getElementById('goal-edit-title');
  const targetField = document.getElementById('goal-edit-target-amount');
  const periodField = document.getElementById('goal-edit-period');
  const deadlineField = document.getElementById('goal-edit-deadline');
  if (
    !(idField instanceof HTMLInputElement) ||
    !(titleField instanceof HTMLInputElement) ||
    !(targetField instanceof HTMLInputElement) ||
    !(periodField instanceof HTMLSelectElement) ||
    !(deadlineField instanceof HTMLInputElement)
  ) {
    return;
  }

  idField.value = trigger.dataset.goalId ?? '';
  titleField.value = trigger.dataset.goalTitle ?? '';
  targetField.value = trigger.dataset.goalTarget ?? '';
  periodField.value = trigger.dataset.goalPeriod ?? 'month';
  deadlineField.value = trigger.dataset.goalDeadline ?? '';
};

const openEditModal = (trigger) => {
  const modal = getEditModal();
  if (modal === null) {
    return;
  }
  setEditFormValues(trigger);
  modal.hidden = false;
  document.body.classList.add('modal-open');
};

const closeEditModal = () => {
  const modal = getEditModal();
  if (modal === null) {
    return;
  }
  modal.hidden = true;
  document.body.classList.remove('modal-open');
};

const getConfirmModal = () => {
  const modal = document.getElementById(CONFIRM_MODAL_ID);
  return modal instanceof HTMLElement ? modal : null;
};

const closeConfirmModal = () => {
  const modal = getConfirmModal();
  if (modal === null) {
    return;
  }
  modal.hidden = true;
  document.body.classList.remove('modal-open');
};

const submitConfirmForm = (returnFunds) => {
  const form = document.getElementById('goal-confirm-form');
  const returnFundsInput = document.getElementById('goal-confirm-return-funds');

  if (!(form instanceof HTMLFormElement) || !(returnFundsInput instanceof HTMLInputElement)) {
    return;
  }

  returnFundsInput.value = returnFunds ? '1' : '0';
  form.submit();
};

const openConfirmModal = (trigger) => {
  if (!(trigger instanceof HTMLElement)) {
    return;
  }

  const modal = getConfirmModal();
  const form = document.getElementById('goal-confirm-form');
  const desc = document.getElementById('goal-confirm-description');
  const amountEl = document.getElementById('goal-confirm-amount');
  const goalIdField = document.getElementById('goal-confirm-goal-id');
  const statusInput = document.getElementById('goal-confirm-status-input');

  if (
    modal === null ||
    !(form instanceof HTMLFormElement) ||
    !(desc instanceof HTMLElement) ||
    !(amountEl instanceof HTMLElement) ||
    !(goalIdField instanceof HTMLInputElement) ||
    !(statusInput instanceof HTMLInputElement)
  ) {
    return;
  }

  const period = document.body.dataset.savingsPeriod ?? 'month';
  const action = trigger.dataset.goalConfirmAction ?? '';
  const goalId = trigger.dataset.goalId ?? '';
  const title = trigger.dataset.goalTitle ?? '';
  const amount = trigger.dataset.goalAmount ?? '';

  const deleteUrl = `/savings/delete?period=${encodeURIComponent(period)}`;
  const statusUrl = `/savings/status?period=${encodeURIComponent(period)}`;

  if (action === 'delete') {
    form.action = deleteUrl;
    statusInput.name = '';
    desc.textContent = `Удалить цель «${title}»?`;
  } else if (action === 'cancel') {
    form.action = statusUrl;
    statusInput.name = 'status';
    desc.textContent = `Отменить цель «${title}»?`;
  } else {
    return;
  }

  goalIdField.value = goalId;
  amountEl.textContent = amount;
  modal.hidden = false;
  document.body.classList.add('modal-open');

  const focusable = modal.querySelectorAll(FOCUSABLE_SELECTOR);
  const first = focusable.length > 0 ? focusable[0] : null;
  if (first instanceof HTMLElement) {
    first.focus();
  }
};

const bindConfirmButtons = () => {
  const btnReturn = document.getElementById('goal-confirm-btn-return');
  const btnKeep = document.getElementById('goal-confirm-btn-keep');

  if (btnReturn instanceof HTMLButtonElement) {
    btnReturn.addEventListener('click', () => submitConfirmForm(true));
  }

  if (btnKeep instanceof HTMLButtonElement) {
    btnKeep.addEventListener('click', () => submitConfirmForm(false));
  }
};

const bindModalOpen = () => {
  const buttons = document.querySelectorAll(MODAL_OPEN_SELECTOR);
  buttons.forEach((button) => {
    button.addEventListener('click', openModal);
  });
};

const bindModalClose = () => {
  const buttons = document.querySelectorAll(MODAL_CLOSE_SELECTOR);
  buttons.forEach((button) => {
    button.addEventListener('click', closeModal);
  });
};

const bindEscapeClose = () => {
  document.addEventListener('keydown', (event) => {
    if (event.key !== ESCAPE_KEY) {
      return;
    }

    const confirmModal = getConfirmModal();
    if (confirmModal !== null && !confirmModal.hidden) {
      closeConfirmModal();
      return;
    }

    const elements = getModalElements();
    if (elements !== null && !elements.modal.hidden) {
      closeModal();
    }

    const editModal = getEditModal();
    if (editModal !== null && !editModal.hidden) {
      closeEditModal();
    }
  });
};

const bindEditModalOpen = () => {
  const buttons = document.querySelectorAll(EDIT_MODAL_OPEN_SELECTOR);
  buttons.forEach((button) => {
    if (!(button instanceof HTMLElement)) {
      return;
    }
    button.addEventListener('click', () => openEditModal(button));
  });
};

const bindEditModalClose = () => {
  const buttons = document.querySelectorAll(EDIT_MODAL_CLOSE_SELECTOR);
  buttons.forEach((button) => {
    button.addEventListener('click', closeEditModal);
  });
};

const bindConfirmOpen = () => {
  const buttons = document.querySelectorAll(CONFIRM_OPEN_SELECTOR);
  buttons.forEach((button) => {
    if (!(button instanceof HTMLElement)) {
      return;
    }
    button.addEventListener('click', () => openConfirmModal(button));
  });
};

const bindConfirmClose = () => {
  const buttons = document.querySelectorAll(CONFIRM_CLOSE_SELECTOR);
  buttons.forEach((button) => {
    button.addEventListener('click', closeConfirmModal);
  });
};

const initGoalModal = () => {
  bindModalOpen();
  bindModalClose();
  bindEditModalOpen();
  bindEditModalClose();
  bindConfirmOpen();
  bindConfirmClose();
  bindConfirmButtons();
  bindEscapeClose();

  const editIdField = document.getElementById('goal-edit-id');
  if (editIdField instanceof HTMLInputElement && editIdField.value !== '') {
    const modal = getEditModal();
    if (modal !== null) {
      modal.hidden = false;
      document.body.classList.add('modal-open');
    }
  }
};

document.addEventListener('DOMContentLoaded', initGoalModal);
