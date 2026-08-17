(function () {
  'use strict';

  var controller = null;
  var resultsSelector = '[data-itk-catalog-results]';
  var toolbarSelector = '[data-itk-catalog-toolbar]';

  function supported() {
    return Boolean(window.fetch && window.DOMParser && window.history && window.URL && window.FormData && window.AbortController);
  }

  function sameOrigin(url) {
    try {
      return new URL(url, window.location.href).origin === window.location.origin;
    } catch (error) {
      return false;
    }
  }

  function formUrl(form) {
    var url = new URL(form.action || window.location.href, window.location.href);
    var data = new FormData(form);
    var multi = {};
    var ranges = {};

    url.search = '';

    data.forEach(function (value, rawKey) {
      if (typeof value !== 'string' || value === '') return;

      var multiMatch = rawKey.match(/^(.*)\[\]$/);
      if (multiMatch) {
        var multiKey = multiMatch[1];
        if (!multi[multiKey]) multi[multiKey] = [];
        if (multi[multiKey].indexOf(value) === -1) multi[multiKey].push(value);
        return;
      }

      var rangeMatch = rawKey.match(/^(.*)\[(min|max)\]$/);
      if (rangeMatch) {
        var rangeKey = rangeMatch[1];
        if (!ranges[rangeKey]) ranges[rangeKey] = { min: '', max: '' };
        ranges[rangeKey][rangeMatch[2]] = value;
        return;
      }

      url.searchParams.set(rawKey, value);
    });

    Object.keys(multi).sort().forEach(function (key) {
      if (multi[key].length) url.searchParams.set(key, multi[key].join(','));
    });

    Object.keys(ranges).sort().forEach(function (key) {
      var range = ranges[key];
      if (range.min !== '' || range.max !== '') {
        url.searchParams.set(key, range.min + '-' + range.max);
      }
    });

    url.searchParams.sort();
    return url.toString();
  }

  function announce(message) {
    var status = document.querySelector('[data-itk-catalog-live-status]');
    if (!status) return;
    status.textContent = '';
    window.setTimeout(function () {
      status.textContent = message || 'Products updated.';
    }, 20);
  }

  function setBusy(busy) {
    var results = document.querySelector(resultsSelector);
    var toolbar = document.querySelector(toolbarSelector);

    if (results) {
      results.setAttribute('aria-busy', busy ? 'true' : 'false');
      results.classList.toggle('is-loading', busy);
    }
    if (toolbar) {
      toolbar.classList.toggle('is-loading', busy);
    }
  }

  function importedClone(source) {
    return document.importNode ? document.importNode(source, true) : source.cloneNode(true);
  }

  function replaceCatalog(sourceDocument) {
    var currentResults = document.querySelector(resultsSelector);
    var sourceResults = sourceDocument.querySelector(resultsSelector);
    if (!currentResults || !sourceResults) return false;

    currentResults.replaceWith(importedClone(sourceResults));

    var currentToolbar = document.querySelector(toolbarSelector);
    var sourceToolbar = sourceDocument.querySelector(toolbarSelector);
    if (currentToolbar && sourceToolbar) {
      currentToolbar.replaceWith(importedClone(sourceToolbar));
    }

    if (sourceDocument.title) document.title = sourceDocument.title;
    return true;
  }

  function fallback(url) {
    window.location.assign(url);
  }

  async function navigate(url, mode) {
    var requested = new URL(url, window.location.href).toString();
    if (!sameOrigin(requested)) {
      fallback(requested);
      return;
    }

    if (controller) controller.abort();
    controller = new AbortController();
    setBusy(true);

    try {
      var response = await fetch(requested, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-ITK-Commerce-Request': 'catalog'
        },
        signal: controller.signal
      });

      if (!response.ok) throw new Error('Catalog request failed with HTTP ' + response.status);

      var html = await response.text();
      var sourceDocument = new DOMParser().parseFromString(html, 'text/html');
      if (!replaceCatalog(sourceDocument)) throw new Error('Catalog response is missing the result boundary.');

      if (mode === 'push') {
        window.history.pushState({ itkCommerceCatalog: true }, '', requested);
      } else if (mode === 'replace') {
        window.history.replaceState({ itkCommerceCatalog: true }, '', requested);
      }

      setBusy(false);
      announce('Products updated.');
      document.dispatchEvent(new CustomEvent('itk:catalog-updated', { detail: { url: requested } }));
    } catch (error) {
      if (error && error.name === 'AbortError') return;
      setBusy(false);
      fallback(requested);
    } finally {
      controller = null;
    }
  }

  function eligibleLink(link, event) {
    if (!link || !link.href || !sameOrigin(link.href)) return false;
    if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0)) return false;
    if (link.hasAttribute('download')) return false;
    if (link.target && link.target !== '_self') return false;
    return true;
  }

  function init() {
    if (!supported() || !document.querySelector(resultsSelector)) return;

    document.documentElement.classList.add('itk-catalog-async-ready');
    window.history.replaceState({ itkCommerceCatalog: true }, '', window.location.href);

    document.addEventListener('submit', function (event) {
      var form = event.target.closest('.itk-filter-form');
      if (!form || String(form.method || 'get').toLowerCase() !== 'get') return;

      var url = formUrl(form);
      if (!sameOrigin(url)) return;

      event.preventDefault();
      navigate(url, 'push');
    });

    document.addEventListener('click', function (event) {
      var link = event.target.closest('.itk-active-filter, .itk-filter-clear');
      if (!eligibleLink(link, event)) return;

      event.preventDefault();
      navigate(link.href, 'push');
    });

    window.addEventListener('popstate', function () {
      navigate(window.location.href, 'none');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
