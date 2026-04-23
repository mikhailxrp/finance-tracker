/**
 * Управление выпадающим меню профиля в шапке.
 */
(function () {
  'use strict';

  const toggle = document.querySelector('[data-profile-toggle]');
  const menu = document.querySelector('[data-profile-menu]');

  if (!toggle || !menu) {
    return;
  }

  function openDropdown() {
    menu.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
  }

  function closeDropdown() {
    menu.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
  }

  function toggleDropdown() {
    if (menu.hidden) {
      openDropdown();
    } else {
      closeDropdown();
    }
  }

  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleDropdown();
  });

  document.addEventListener('click', (e) => {
    if (!menu.hidden && !menu.contains(e.target) && e.target !== toggle) {
      closeDropdown();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !menu.hidden) {
      closeDropdown();
      toggle.focus();
    }
  });
})();
