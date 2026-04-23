const PRESET_SELECTOR = '[data-strategy-preset]';
const STRATEGY_FORM_SELECTOR = '[data-strategy-form]';
const GENERATE_BUTTON_ID = 'generate-btn';
const BUTTON_LOADING_CLASS = 'btn-loading';
const SPINNER_CLASS = 'spinner';
const BUTTON_LOADING_TEXT = 'Генерируем стратегию...';
const REQUEST_TEXTAREA_ID = 'strategy-request';
const ACCORDION_ITEM_SELECTOR = '[data-accordion-item]';
const ACCORDION_TOGGLE_SELECTOR = '[data-accordion-toggle]';
const OPEN_CLASS = 'strategy-history-item--open';

const bindPresetButtons = () => {
  const textarea = document.getElementById(REQUEST_TEXTAREA_ID);
  if (!(textarea instanceof HTMLTextAreaElement)) {
    return;
  }

  const presets = document.querySelectorAll(PRESET_SELECTOR);
  presets.forEach((presetButton) => {
    if (!(presetButton instanceof HTMLButtonElement)) {
      return;
    }

    presetButton.addEventListener('click', () => {
      const presetText = presetButton.dataset.strategyPreset ?? '';
      textarea.value = presetText;
      textarea.focus();
    });
  });
};

const closeAccordionItem = (item) => {
  const toggle = item.querySelector(ACCORDION_TOGGLE_SELECTOR);
  const panelId = toggle instanceof HTMLButtonElement ? toggle.getAttribute('aria-controls') : null;
  const panel = panelId === null ? null : document.getElementById(panelId);

  if (toggle instanceof HTMLButtonElement) {
    toggle.setAttribute('aria-expanded', 'false');
  }

  if (panel instanceof HTMLElement) {
    panel.hidden = true;
  }

  item.classList.remove(OPEN_CLASS);
};

const openAccordionItem = (item) => {
  const toggle = item.querySelector(ACCORDION_TOGGLE_SELECTOR);
  const panelId = toggle instanceof HTMLButtonElement ? toggle.getAttribute('aria-controls') : null;
  const panel = panelId === null ? null : document.getElementById(panelId);

  if (toggle instanceof HTMLButtonElement) {
    toggle.setAttribute('aria-expanded', 'true');
  }

  if (panel instanceof HTMLElement) {
    panel.hidden = false;
  }

  item.classList.add(OPEN_CLASS);
};

const bindAccordion = () => {
  const items = document.querySelectorAll(ACCORDION_ITEM_SELECTOR);

  items.forEach((item) => {
    if (!(item instanceof HTMLElement)) {
      return;
    }

    const toggle = item.querySelector(ACCORDION_TOGGLE_SELECTOR);
    if (!(toggle instanceof HTMLButtonElement)) {
      return;
    }

    toggle.addEventListener('click', () => {
      const isOpen = item.classList.contains(OPEN_CLASS);

      items.forEach((otherItem) => {
        if (!(otherItem instanceof HTMLElement)) {
          return;
        }
        closeAccordionItem(otherItem);
      });

      if (!isOpen) {
        openAccordionItem(item);
      }
    });
  });
};

const bindFormLoadingState = () => {
  const form = document.querySelector(STRATEGY_FORM_SELECTOR);
  if (!(form instanceof HTMLFormElement)) {
    return;
  }

  const submitButton = document.getElementById(GENERATE_BUTTON_ID);
  if (!(submitButton instanceof HTMLButtonElement)) {
    return;
  }

  form.addEventListener('submit', () => {
    if (submitButton.disabled) {
      return;
    }

    submitButton.disabled = true;
    submitButton.classList.add(BUTTON_LOADING_CLASS);
    submitButton.setAttribute('aria-busy', 'true');
    submitButton.textContent = '';

    const spinner = document.createElement('span');
    spinner.className = SPINNER_CLASS;
    spinner.setAttribute('aria-hidden', 'true');
    submitButton.append(spinner);
    submitButton.append(` ${BUTTON_LOADING_TEXT}`);
  });
};

const initStrategyPage = () => {
  bindPresetButtons();
  bindAccordion();
  bindFormLoadingState();
};

document.addEventListener('DOMContentLoaded', initStrategyPage);
