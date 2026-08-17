(function () {
  'use strict';

  var root = null;
  var panel = null;
  var lastTrigger = null;
  var refreshPromise = null;
  var focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

  function options() {
    return window.ITKCommerceMiniCart || {};
  }

  function triggers() {
    return Array.prototype.slice.call(document.querySelectorAll('.itk-header-action--cart, [data-itk-mini-cart-trigger]'));
  }

  function setTriggerState(open) {
    triggers().forEach(function (trigger) {
      trigger.setAttribute('aria-controls', 'itk-mini-cart');
      trigger.setAttribute('aria-haspopup', 'dialog');
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  function focusableElements() {
    if (!panel) {
      return [];
    }
    return Array.prototype.slice.call(panel.querySelectorAll(focusableSelector)).filter(function (element) {
      return element.offsetWidth > 0 || element.offsetHeight > 0 || element === document.activeElement;
    });
  }

  function open(trigger) {
    if (!root || root.classList.contains('is-open')) {
      return;
    }
    lastTrigger = trigger || document.activeElement;
    root.classList.add('is-open');
    root.setAttribute('aria-hidden', 'false');
    document.body.classList.add('itk-mini-cart-open');
    setTriggerState(true);
    window.requestAnimationFrame(function () {
      var closeButton = root.querySelector('[data-itk-mini-cart-close]');
      (closeButton || panel).focus();
    });
  }

  function close(restoreFocus) {
    if (!root || !root.classList.contains('is-open')) {
      return;
    }
    root.classList.remove('is-open');
    root.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('itk-mini-cart-open');
    setTriggerState(false);
    if (restoreFocus !== false && lastTrigger && typeof lastTrigger.focus === 'function') {
      lastTrigger.focus();
    }
  }

  function shouldHandleTrigger(event, trigger) {
    if (!trigger || event.defaultPrevented) {
      return false;
    }
    if (event.button !== undefined && event.button !== 0) {
      return false;
    }
    if (trigger.hasAttribute('download')) {
      return false;
    }
    var target = trigger.getAttribute('target');
    if (target && target.toLowerCase() !== '_self') {
      return false;
    }
    return !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
  }

  function handleDocumentClick(event) {
    var trigger = event.target.closest('.itk-header-action--cart, [data-itk-mini-cart-trigger]');
    if (trigger && shouldHandleTrigger(event, trigger)) {
      event.preventDefault();
      open(trigger);
      return;
    }
    if (event.target.closest('[data-itk-mini-cart-close]')) {
      event.preventDefault();
      close(true);
      return;
    }
    if (event.target.closest('[data-itk-mini-cart-backdrop]') && options().closeOnBackdrop !== false) {
      close(true);
    }
  }

  function handleKeydown(event) {
    if (!root || !root.classList.contains('is-open')) {
      return;
    }
    if (event.key === 'Escape') {
      event.preventDefault();
      close(true);
      return;
    }
    if (event.key !== 'Tab') {
      return;
    }
    var elements = focusableElements();
    if (!elements.length) {
      event.preventDefault();
      panel.focus();
      return;
    }
    var first = elements[0];
    var last = elements[elements.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function replaceFragment(selector, html) {
    var targets;
    try {
      targets = Array.prototype.slice.call(document.querySelectorAll(selector));
    } catch (error) {
      return;
    }
    targets.forEach(function (target) {
      var template = document.createElement('template');
      template.innerHTML = String(html || '').trim();
      var replacement = template.content.firstElementChild;
      if (replacement) {
        target.replaceWith(replacement.cloneNode(true));
      }
    });
  }

  function applyFragments(data) {
    if (!data || !data.fragments || typeof data.fragments !== 'object') {
      return;
    }
    Object.keys(data.fragments).forEach(function (selector) {
      replaceFragment(selector, data.fragments[selector]);
    });
  }

  function refreshFragments() {
    var refreshUrl = options().refreshUrl || '';
    if (!refreshUrl || typeof window.fetch !== 'function') {
      return Promise.resolve();
    }
    if (refreshPromise) {
      return refreshPromise;
    }
    refreshPromise = window.fetch(refreshUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Mini-cart fragment refresh failed with HTTP ' + response.status);
      }
      return response.json();
    }).then(function (data) {
      applyFragments(data);
      return data;
    }).catch(function () {
      return null;
    });
    refreshPromise = refreshPromise.then(function (result) {
      refreshPromise = null;
      return result;
    }, function (error) {
      refreshPromise = null;
      throw error;
    });
    return refreshPromise;
  }

  function defaultTrigger() {
    return document.querySelector('.itk-header-action--cart, [data-itk-mini-cart-trigger]');
  }

  function bindWooCommerceEvents() {
    if (window.jQuery) {
      window.jQuery(document.body).on('added_to_cart', function () {
        if (options().openAfterAdd !== false) {
          open(defaultTrigger());
        }
      });
    }
    document.body.addEventListener('wc-blocks_added_to_cart', function () {
      refreshFragments().then(function () {
        if (options().openAfterAdd !== false) {
          open(defaultTrigger());
        }
      });
    });
    document.body.addEventListener('wc-blocks_removed_from_cart', function () {
      refreshFragments();
    });
  }

  function init() {
    root = document.querySelector('[data-itk-mini-cart]');
    if (!root) {
      return;
    }
    panel = root.querySelector('[data-itk-mini-cart-panel]');
    if (!panel) {
      return;
    }
    setTriggerState(false);
    document.addEventListener('click', handleDocumentClick);
    document.addEventListener('keydown', handleKeydown);
    bindWooCommerceEvents();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
