const SELECTORS = {
  toggleButton: '.password-eye',
  passwordInput: '.password-wrap input[type="password"], .password-wrap input[type="text"]',
};

const ICONS = {
  hidden: '◉',
  visible: '👁',
};

const LABELS = {
  show: 'Показать пароль',
  hide: 'Скрыть пароль',
};

const togglePasswordVisibility = (button) => {
  const wrap = button.closest('.password-wrap');
  if (!wrap) {
    return;
  }

  const input = wrap.querySelector(SELECTORS.passwordInput);
  if (!input) {
    return;
  }

  const isPassword = input.type === 'password';

  input.type = isPassword ? 'text' : 'password';
  button.textContent = isPassword ? ICONS.visible : ICONS.hidden;
  button.setAttribute('aria-label', isPassword ? LABELS.hide : LABELS.show);
};

const initPasswordToggle = () => {
  const buttons = document.querySelectorAll(SELECTORS.toggleButton);

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      togglePasswordVisibility(button);
    });
  });
};

initPasswordToggle();
