const SELECTORS = {
  form: 'form[data-submit-loading]',
  submitButtons: 'button[type="submit"], input[type="submit"]',
};

const ATTRIBUTES = {
  busy: 'aria-busy',
  loading: 'data-is-loading',
  originalLabel: 'data-original-label',
  loadingText: 'data-loading-text',
  overlayEnabled: 'data-submit-overlay',
};

const OVERLAY_TEXT_DEFAULT = 'Отправка...';

const createOverlay = (form, overlayText) => {
  const overlay = document.createElement('div');
  overlay.className = 'submit-loader__overlay';
  overlay.setAttribute('role', 'status');
  overlay.setAttribute('aria-live', 'polite');
  overlay.setAttribute('aria-atomic', 'true');

  const spinner = document.createElement('span');
  spinner.className = 'submit-loader__spinner';
  spinner.setAttribute('aria-hidden', 'true');

  const text = document.createElement('span');
  text.textContent = overlayText;

  overlay.append(spinner, text);
  form.append(overlay);
};

const setSubmitButtonState = (button) => {
  if (!(button instanceof HTMLButtonElement)) {
    button.disabled = true;
    return;
  }

  const customLoadingText = button.getAttribute(ATTRIBUTES.loadingText) ?? OVERLAY_TEXT_DEFAULT;
  const originalLabel = button.getAttribute(ATTRIBUTES.originalLabel) ?? button.textContent ?? '';

  button.setAttribute(ATTRIBUTES.originalLabel, originalLabel);
  button.setAttribute(ATTRIBUTES.loading, 'true');
  button.textContent = customLoadingText;
  button.disabled = true;
};

const setupFormLoader = (form) => {
  form.addEventListener('submit', () => {
    if (form.getAttribute(ATTRIBUTES.busy) === 'true') {
      return;
    }

    try {
      form.setAttribute(ATTRIBUTES.busy, 'true');

      const overlayEnabled = form.getAttribute(ATTRIBUTES.overlayEnabled) === 'true';
      if (overlayEnabled) {
        const overlayText = form.getAttribute(ATTRIBUTES.loadingText) ?? OVERLAY_TEXT_DEFAULT;
        createOverlay(form, overlayText);
      }

      const submitButtons = form.querySelectorAll(SELECTORS.submitButtons);
      submitButtons.forEach((button) => {
        setSubmitButtonState(button);
      });
    } catch (error) {
      console.error('Unable to enable submit loader.', error);
    }
  });
};

const initFormSubmitLoader = () => {
  const forms = document.querySelectorAll(SELECTORS.form);
  forms.forEach((form) => {
    setupFormLoader(form);
  });
};

initFormSubmitLoader();
