(() => {
  'use strict';

  const toggle = document.querySelector('[data-itk-menu-toggle]');
  const panel = document.querySelector('[data-itk-navigation-panel]');

  if (!toggle || !panel) {
    return;
  }

  const closeMenu = () => {
    panel.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('itk-menu-open');
  };

  const openMenu = () => {
    panel.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('itk-menu-open');

    const firstLink = panel.querySelector('a, button');
    if (firstLink) {
      firstLink.focus({ preventScroll: true });
    }
  };

  toggle.addEventListener('click', () => {
    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    expanded ? closeMenu() : openMenu();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && panel.classList.contains('is-open')) {
      closeMenu();
      toggle.focus();
    }
  });

  document.addEventListener('click', (event) => {
    if (!panel.classList.contains('is-open')) {
      return;
    }

    if (!panel.contains(event.target) && !toggle.contains(event.target)) {
      closeMenu();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 782) {
      closeMenu();
    }
  });
})();
