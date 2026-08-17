(function () {
  'use strict';

  var config = window.ITKCommerceFilterDrawer || {};
  var breakpoint = Number(config.breakpoint || 760);
  var media = window.matchMedia('(max-width: ' + breakpoint + 'px)');
  var counter = 0;

  function label(key, fallback) {
    return config.labels && typeof config.labels[key] === 'string' && config.labels[key]
      ? config.labels[key]
      : fallback;
  }

  function focusable(panel) {
    return Array.from(panel.querySelectorAll(
      'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )).filter(function (element) {
      return !element.hidden && element.getAttribute('aria-hidden') !== 'true' && element.getClientRects().length > 0;
    });
  }

  function enhance(root) {
    if (!root || root.dataset.itkDrawerEnhanced === '1') return;

    var details = root.querySelector('.itk-filter-popover');
    var trigger = details && details.querySelector(':scope > .itk-filter-trigger');
    var panel = details && details.querySelector(':scope > .itk-filter-popover__panel');
    if (!details || !trigger || !panel) return;

    root.dataset.itkDrawerEnhanced = '1';
    counter += 1;

    var panelId = panel.id || 'itk-filter-drawer-' + counter;
    panel.id = panelId;
    trigger.setAttribute('aria-controls', panelId);
    trigger.setAttribute('aria-expanded', details.open ? 'true' : 'false');

    var header = document.createElement('div');
    header.className = 'itk-filter-drawer__header';

    var title = document.createElement('strong');
    title.className = 'itk-filter-drawer__title';
    title.textContent = label('title', 'Filters');

    var closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'itk-filter-drawer__close';
    closeButton.setAttribute('aria-label', label('close', 'Close filters'));
    closeButton.innerHTML = '<span aria-hidden="true">×</span>';

    header.appendChild(title);
    header.appendChild(closeButton);
    panel.insertBefore(header, panel.firstChild);

    var backdrop = document.createElement('div');
    backdrop.className = 'itk-filter-drawer__backdrop';
    backdrop.setAttribute('aria-hidden', 'true');
    details.appendChild(backdrop);

    var lastFocus = null;

    function isOpen() {
      return details.classList.contains('is-drawer-open');
    }

    function setDialogState(active) {
      if (active) {
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('aria-label', label('title', 'Filters'));
      } else {
        panel.removeAttribute('role');
        panel.removeAttribute('aria-modal');
        panel.removeAttribute('aria-label');
      }
    }

    function openDrawer() {
      if (!media.matches || isOpen()) return;

      lastFocus = document.activeElement;
      details.open = true;
      details.classList.add('is-drawer-open');
      document.body.classList.add('itk-filter-drawer-open');
      trigger.setAttribute('aria-expanded', 'true');
      setDialogState(true);

      window.requestAnimationFrame(function () {
        closeButton.focus();
      });
    }

    function closeDrawer(restoreFocus) {
      if (!isOpen()) return;

      details.classList.remove('is-drawer-open');
      document.body.classList.remove('itk-filter-drawer-open');
      trigger.setAttribute('aria-expanded', 'false');
      setDialogState(false);
      details.open = false;

      if (restoreFocus !== false) {
        var target = lastFocus && document.contains(lastFocus) ? lastFocus : trigger;
        window.requestAnimationFrame(function () {
          target.focus();
        });
      }
    }

    function syncMode() {
      if (media.matches) {
        document.documentElement.classList.add('itk-filter-offcanvas-ready');
        if (!isOpen()) {
          details.open = false;
          trigger.setAttribute('aria-expanded', 'false');
          setDialogState(false);
        }
      } else {
        if (isOpen()) closeDrawer(false);
        document.documentElement.classList.remove('itk-filter-offcanvas-ready');
        document.body.classList.remove('itk-filter-drawer-open');
        trigger.setAttribute('aria-expanded', details.open ? 'true' : 'false');
        setDialogState(false);
      }
    }

    trigger.addEventListener('click', function (event) {
      if (!media.matches) {
        window.setTimeout(function () {
          trigger.setAttribute('aria-expanded', details.open ? 'true' : 'false');
        }, 0);
        return;
      }

      event.preventDefault();
      if (isOpen()) closeDrawer(true);
      else openDrawer();
    });

    closeButton.addEventListener('click', function () {
      closeDrawer(true);
    });

    backdrop.addEventListener('click', function () {
      closeDrawer(true);
    });

    panel.addEventListener('keydown', function (event) {
      if (!media.matches || !isOpen()) return;

      if (event.key === 'Escape') {
        event.preventDefault();
        closeDrawer(true);
        return;
      }

      if (event.key !== 'Tab') return;

      var items = focusable(panel);
      if (!items.length) {
        event.preventDefault();
        closeButton.focus();
        return;
      }

      var first = items[0];
      var last = items[items.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    panel.addEventListener('submit', function () {
      if (media.matches && isOpen()) closeDrawer(false);
    }, true);

    if (typeof media.addEventListener === 'function') media.addEventListener('change', syncMode);
    else if (typeof media.addListener === 'function') media.addListener(syncMode);

    syncMode();
  }

  function enhanceAll() {
    document.querySelectorAll('[data-itk-filter-ui]').forEach(enhance);

    if (media.matches && document.querySelector('[data-itk-filter-ui]')) {
      document.documentElement.classList.add('itk-filter-offcanvas-ready');
    } else if (!media.matches) {
      document.documentElement.classList.remove('itk-filter-offcanvas-ready');
    }
  }

  function init() {
    enhanceAll();
    document.addEventListener('itk:catalog-updated', enhanceAll);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
