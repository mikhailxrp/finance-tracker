const CREATE_OPEN_SELECTOR = '[data-credit-modal-open]';
const CREATE_CLOSE_SELECTOR = '[data-credit-modal-close]';
const CREATE_MODAL_ID = 'credit-create-modal';
const EDIT_OPEN_SELECTOR = '[data-credit-edit-open]';
const EDIT_CLOSE_SELECTOR = '[data-credit-edit-close]';
const EDIT_MODAL_ID = 'credit-edit-modal';
const ESCAPE_KEY = 'Escape';
const FOCUSABLE_SELECTOR =
  'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

const getCreateModal = () => {
  const modal = document.getElementById(CREATE_MODAL_ID);
  return modal instanceof HTMLElement ? modal : null;
};

const openCreateModal = () => {
  const modal = getCreateModal();
  if (modal === null) {
    return;
  }
  modal.hidden = false;
  document.body.classList.add('modal-open');
  const focusable = modal.querySelectorAll(FOCUSABLE_SELECTOR);
  const first = focusable.length > 0 ? focusable[0] : null;
  if (first instanceof HTMLElement) {
    first.focus();
  }
};

const closeCreateModal = () => {
  const modal = getCreateModal();
  if (modal === null) {
    return;
  }
  modal.hidden = true;
  document.body.classList.remove('modal-open');
};

const getEditModal = () => {
  const modal = document.getElementById(EDIT_MODAL_ID);
  return modal instanceof HTMLElement ? modal : null;
};

const setEditFormValues = (trigger) => {
  const idField = document.getElementById('credit-edit-id');
  const titleField = document.getElementById('credit-edit-title');
  const totalField = document.getElementById('credit-edit-total');
  const rateField = document.getElementById('credit-edit-rate');
  const startField = document.getElementById('credit-edit-start');
  if (
    !(idField instanceof HTMLInputElement) ||
    !(titleField instanceof HTMLInputElement) ||
    !(totalField instanceof HTMLInputElement) ||
    !(rateField instanceof HTMLInputElement) ||
    !(startField instanceof HTMLInputElement)
  ) {
    return;
  }

  idField.value = trigger.dataset.creditId ?? '';
  titleField.value = trigger.dataset.creditTitle ?? '';
  totalField.value = trigger.dataset.creditTotal ?? '';
  rateField.value = trigger.dataset.creditRate ?? '';
  startField.value = trigger.dataset.creditStart ?? '';
};

const openEditModal = (trigger) => {
  const modal = getEditModal();
  if (modal === null) {
    return;
  }
  setEditFormValues(trigger);
  modal.hidden = false;
  document.body.classList.add('modal-open');
  const focusable = modal.querySelectorAll(FOCUSABLE_SELECTOR);
  const first = focusable.length > 0 ? focusable[0] : null;
  if (first instanceof HTMLElement) {
    first.focus();
  }
};

const closeEditModal = () => {
  const modal = getEditModal();
  if (modal === null) {
    return;
  }
  modal.hidden = true;
  document.body.classList.remove('modal-open');
};

const openEditModalFromSession = () => {
  const body = document.body;
  if (!(body instanceof HTMLElement)) {
    return;
  }
  if (body.dataset.creditEditError !== '1') {
    return;
  }
  const modal = getEditModal();
  if (modal === null) {
    return;
  }
  modal.hidden = false;
  document.body.classList.add('modal-open');
};

document.addEventListener('DOMContentLoaded', () => {
  openEditModalFromSession();
});

document.addEventListener('click', (event) => {
  const target = event.target;
  if (!(target instanceof Element)) {
    return;
  }

  if (target.closest(CREATE_OPEN_SELECTOR) !== null) {
    event.preventDefault();
    openCreateModal();
    return;
  }

  if (target.closest(CREATE_CLOSE_SELECTOR) !== null) {
    event.preventDefault();
    closeCreateModal();
    return;
  }

  const editTrigger = target.closest(EDIT_OPEN_SELECTOR);
  if (editTrigger instanceof HTMLElement) {
    event.preventDefault();
    openEditModal(editTrigger);
    return;
  }

  if (target.closest(EDIT_CLOSE_SELECTOR) !== null) {
    event.preventDefault();
    closeEditModal();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key !== ESCAPE_KEY) {
    return;
  }
  const createModal = getCreateModal();
  if (createModal !== null && !createModal.hidden) {
    closeCreateModal();
    return;
  }
  const editModal = getEditModal();
  if (editModal !== null && !editModal.hidden) {
    closeEditModal();
  }
});
