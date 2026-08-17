(function () {
  'use strict';

  function closeItem(item) {
    var toggle = item.querySelector(':scope > [data-itk-mega-toggle]');
    item.classList.remove('itk-mega-open');
    if (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
    }
  }

  function closeOthers(except) {
    document.querySelectorAll('.itk-menu-item--mega-rich.itk-mega-open').forEach(function (item) {
      if (item !== except) {
        closeItem(item);
      }
    });
  }

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-itk-mega-toggle]');
    if (toggle) {
      var item = toggle.closest('.itk-menu-item--mega-rich');
      if (!item) {
        return;
      }

      event.preventDefault();
      var open = !item.classList.contains('itk-mega-open');
      closeOthers(item);
      item.classList.toggle('itk-mega-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      return;
    }

    if (!event.target.closest('.itk-menu-item--mega-rich')) {
      closeOthers(null);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
      return;
    }

    var active = document.activeElement && document.activeElement.closest
      ? document.activeElement.closest('.itk-menu-item--mega-rich')
      : null;

    document.querySelectorAll('.itk-menu-item--mega-rich.itk-mega-open').forEach(closeItem);

    if (active) {
      var toggle = active.querySelector(':scope > [data-itk-mega-toggle]');
      if (toggle) {
        toggle.focus();
      }
    }
  });
}());
