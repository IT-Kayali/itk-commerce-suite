(function () {
  'use strict';

  var config = window.ITKCommerceLiveSearch || {};
  var cache = new Map();
  var cacheLimit = 30;

  function text(key, fallback) {
    return config.messages && typeof config.messages[key] === 'string' && config.messages[key]
      ? config.messages[key]
      : fallback;
  }

  function option(key, fallback) {
    return config.options && typeof config.options[key] !== 'undefined'
      ? config.options[key]
      : fallback;
  }

  function safeUrl(value, sameOriginOnly) {
    try {
      var url = new URL(value, window.location.href);
      if (url.protocol !== 'http:' && url.protocol !== 'https:') return '';
      if (sameOriginOnly && url.origin !== window.location.origin) return '';
      return url.toString();
    } catch (error) {
      return '';
    }
  }

  function endpoint(name) {
    var value = config.endpoints && config.endpoints[name];
    return safeUrl(value || '', true);
  }

  function apiUrl(base, params) {
    var url = new URL(base);
    Object.keys(params).forEach(function (key) {
      if (params[key] !== '' && params[key] !== null && typeof params[key] !== 'undefined') {
        url.searchParams.set(key, String(params[key]));
      }
    });
    return url.toString();
  }

  async function fetchArray(url, signal) {
    var response = await fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
      signal: signal
    });

    if (!response.ok) throw new Error('Live search request failed with HTTP ' + response.status);
    var payload = await response.json();
    return Array.isArray(payload) ? payload : [];
  }

  function mergeById(primary, secondary, limit) {
    var seen = new Set();
    var merged = [];

    [primary, secondary].forEach(function (collection) {
      (collection || []).forEach(function (item) {
        var id = item && Number(item.id);
        if (!id || seen.has(id) || merged.length >= limit) return;
        seen.add(id);
        merged.push(item);
      });
    });

    return merged;
  }

  function formatPrice(product) {
    var prices = product && product.prices;
    if (!prices || prices.price === null || typeof prices.price === 'undefined') return '';

    var raw = Number(prices.price);
    var minor = Number(prices.currency_minor_unit);
    var currency = String(prices.currency_code || '');
    if (!Number.isFinite(raw)) return '';
    if (!Number.isFinite(minor) || minor < 0 || minor > 6) minor = 2;

    var amount = raw / Math.pow(10, minor);
    if (!currency) return String(amount);

    try {
      return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: minor,
        maximumFractionDigits: minor
      }).format(amount);
    } catch (error) {
      return amount + ' ' + currency;
    }
  }

  function productOption(product) {
    var href = safeUrl(product && product.permalink, true);
    if (!href) return null;

    var link = document.createElement('a');
    link.className = 'itk-live-search__option itk-live-search__option--product';
    link.href = href;
    link.setAttribute('role', 'option');
    link.dataset.itkLiveSearchOption = '1';

    var imageWrap = document.createElement('span');
    imageWrap.className = 'itk-live-search__image';
    var imageData = product.images && product.images[0];
    var imageSrc = imageData && safeUrl(imageData.thumbnail || imageData.src, false);
    if (imageSrc) {
      var image = document.createElement('img');
      image.src = imageSrc;
      image.alt = imageData.alt || '';
      image.loading = 'lazy';
      image.decoding = 'async';
      imageWrap.appendChild(image);
    }

    var content = document.createElement('span');
    content.className = 'itk-live-search__option-content';

    var name = document.createElement('strong');
    name.className = 'itk-live-search__option-title';
    name.textContent = product.name || '';
    content.appendChild(name);

    var metaParts = [];
    if (product.categories && product.categories[0] && product.categories[0].name) {
      metaParts.push(product.categories[0].name);
    }
    if (product.sku) {
      metaParts.push(text('sku', 'SKU: %s').replace('%s', product.sku));
    }

    if (metaParts.length) {
      var meta = document.createElement('span');
      meta.className = 'itk-live-search__option-meta';
      meta.textContent = metaParts.join(' · ');
      content.appendChild(meta);
    }

    var price = formatPrice(product);
    if (price) {
      var priceNode = document.createElement('span');
      priceNode.className = 'itk-live-search__price';
      priceNode.textContent = price;
      content.appendChild(priceNode);
    }

    link.appendChild(imageWrap);
    link.appendChild(content);
    return link;
  }

  function categoryOption(category) {
    var href = safeUrl(category && category.permalink, true);
    if (!href) return null;

    var link = document.createElement('a');
    link.className = 'itk-live-search__option itk-live-search__option--category';
    link.href = href;
    link.setAttribute('role', 'option');
    link.dataset.itkLiveSearchOption = '1';

    var content = document.createElement('span');
    content.className = 'itk-live-search__option-content';

    var name = document.createElement('strong');
    name.className = 'itk-live-search__option-title';
    name.textContent = category.name || '';
    content.appendChild(name);

    if (typeof category.count !== 'undefined') {
      var count = document.createElement('span');
      count.className = 'itk-live-search__option-meta';
      count.textContent = '(' + String(category.count) + ')';
      content.appendChild(count);
    }

    link.appendChild(content);
    return link;
  }

  function section(titleText, items, renderer) {
    if (!items.length) return null;

    var wrapper = document.createElement('div');
    wrapper.className = 'itk-live-search__section';

    var heading = document.createElement('div');
    heading.className = 'itk-live-search__section-title';
    heading.setAttribute('role', 'presentation');
    heading.textContent = titleText;
    wrapper.appendChild(heading);

    items.forEach(function (item) {
      var node = renderer(item);
      if (node) wrapper.appendChild(node);
    });

    return wrapper;
  }

  function allResultsOption(query) {
    var base = safeUrl(config.searchUrl || '/', true);
    if (!base) return null;

    var url = new URL(base);
    url.searchParams.set('s', query);
    url.searchParams.set('post_type', 'product');

    var link = document.createElement('a');
    link.className = 'itk-live-search__all';
    link.href = url.toString();
    link.setAttribute('role', 'option');
    link.dataset.itkLiveSearchOption = '1';
    link.textContent = text('allResults', 'View all results');
    return link;
  }

  function enhance(root) {
    if (!root || root.dataset.itkLiveSearchEnhanced === '1') return;

    var form = root.querySelector('[data-itk-live-search-form]');
    var input = form && form.querySelector('.itk-live-search__input');
    var panel = form && form.querySelector('[data-itk-live-search-panel]');
    var results = form && form.querySelector('[data-itk-live-search-results]');
    var status = form && form.querySelector('[data-itk-live-search-status]');
    if (!form || !input || !panel || !results || !status) return;

    var productsEndpoint = endpoint('products');
    var categoriesEndpoint = endpoint('categories');
    if (!productsEndpoint) return;

    root.dataset.itkLiveSearchEnhanced = '1';

    var minChars = Math.max(1, Number(option('minChars', 2)) || 2);
    var productLimit = Math.max(1, Math.min(12, Number(option('productLimit', 6)) || 6));
    var categoryLimit = Math.max(0, Math.min(8, Number(option('categoryLimit', 4)) || 4));
    var debounceMs = Math.max(80, Math.min(800, Number(option('debounceMs', 180)) || 180));
    var showCategories = option('showCategories', true) !== false && Boolean(categoriesEndpoint) && categoryLimit > 0;
    var skuMatching = option('skuMatching', true) !== false;
    var timer = null;
    var controller = null;
    var activeIndex = -1;
    var composing = false;

    function options() {
      return Array.from(results.querySelectorAll('[data-itk-live-search-option]'));
    }

    function announce(message) {
      status.textContent = '';
      window.setTimeout(function () {
        status.textContent = message;
      }, 10);
    }

    function setExpanded(expanded) {
      input.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      panel.hidden = !expanded;
      if (!expanded) {
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');
      }
    }

    function setActive(index) {
      var items = options();
      items.forEach(function (item) {
        item.classList.remove('is-active');
        item.setAttribute('aria-selected', 'false');
      });

      if (!items.length || index < 0) {
        activeIndex = -1;
        input.removeAttribute('aria-activedescendant');
        return;
      }

      activeIndex = (index + items.length) % items.length;
      var active = items[activeIndex];
      if (!active.id) active.id = 'itk-live-search-option-' + Date.now() + '-' + activeIndex;
      active.classList.add('is-active');
      active.setAttribute('aria-selected', 'true');
      input.setAttribute('aria-activedescendant', active.id);
      active.scrollIntoView({ block: 'nearest' });
    }

    function renderLoading() {
      results.replaceChildren();
      var loading = document.createElement('div');
      loading.className = 'itk-live-search__message';
      loading.textContent = text('loading', 'Searching…');
      results.appendChild(loading);
      setExpanded(true);
      announce(text('loading', 'Searching…'));
    }

    function renderError() {
      results.replaceChildren();
      var error = document.createElement('div');
      error.className = 'itk-live-search__message';
      error.textContent = text('error', 'Live results are unavailable. Press Enter to search normally.');
      results.appendChild(error);
      setExpanded(true);
      announce(error.textContent);
    }

    function renderPayload(query, payload) {
      results.replaceChildren();
      activeIndex = -1;

      var productSection = section(text('products', 'Products'), payload.products, productOption);
      var categorySection = section(text('categories', 'Categories'), payload.categories, categoryOption);
      if (productSection) results.appendChild(productSection);
      if (categorySection) results.appendChild(categorySection);

      var all = allResultsOption(query);
      if (all) results.appendChild(all);

      var count = options().length;
      if (!payload.products.length && !payload.categories.length) {
        results.replaceChildren();
        var empty = document.createElement('div');
        empty.className = 'itk-live-search__message';
        empty.textContent = text('empty', 'No matching products or categories found.');
        results.appendChild(empty);
        if (all) results.appendChild(all);
        count = options().length;
      }

      setExpanded(true);
      announce(text('resultCount', '%d live results available.').replace('%d', String(count)));
    }

    async function search(query) {
      var normalized = query.trim().slice(0, 80);
      if (normalized.length < minChars) {
        if (controller) controller.abort();
        results.replaceChildren();
        setExpanded(false);
        return;
      }

      var cacheKey = normalized.toLocaleLowerCase();
      if (cache.has(cacheKey)) {
        renderPayload(normalized, cache.get(cacheKey));
        return;
      }

      if (controller) controller.abort();
      var requestController = new AbortController();
      controller = requestController;
      renderLoading();

      var productSearch = fetchArray(
        apiUrl(productsEndpoint, { search: normalized, per_page: productLimit }),
        requestController.signal
      );

      var skuSearch = skuMatching
        ? fetchArray(apiUrl(productsEndpoint, { sku: normalized, per_page: productLimit }), requestController.signal)
        : Promise.resolve([]);

      var categorySearch = showCategories
        ? fetchArray(apiUrl(categoriesEndpoint, { search: normalized, per_page: categoryLimit, hide_empty: true }), requestController.signal)
        : Promise.resolve([]);

      try {
        var settled = await Promise.allSettled([skuSearch, productSearch, categorySearch]);
        if (requestController.signal.aborted) return;

        var successful = settled.filter(function (result) { return result.status === 'fulfilled'; });
        if (!successful.length) throw new Error('All live search requests failed.');

        var skuProducts = settled[0].status === 'fulfilled' ? settled[0].value : [];
        var nameProducts = settled[1].status === 'fulfilled' ? settled[1].value : [];
        var categories = settled[2].status === 'fulfilled' ? settled[2].value.slice(0, categoryLimit) : [];

        var payload = {
          products: mergeById(skuProducts, nameProducts, productLimit),
          categories: categories
        };

        cache.set(cacheKey, payload);
        if (cache.size > cacheLimit) cache.delete(cache.keys().next().value);
        renderPayload(normalized, payload);
      } catch (error) {
        if (error && error.name === 'AbortError') return;
        renderError();
      } finally {
        if (controller === requestController) controller = null;
      }
    }

    function schedule() {
      if (composing) return;
      window.clearTimeout(timer);
      timer = window.setTimeout(function () {
        search(input.value);
      }, debounceMs);
    }

    input.addEventListener('compositionstart', function () {
      composing = true;
    });

    input.addEventListener('compositionend', function () {
      composing = false;
      schedule();
    });

    input.addEventListener('input', schedule);

    input.addEventListener('focus', function () {
      if (options().length || results.children.length) setExpanded(true);
    });

    input.addEventListener('keydown', function (event) {
      var items = options();

      if (event.key === 'ArrowDown' && items.length) {
        event.preventDefault();
        setExpanded(true);
        setActive(activeIndex + 1);
      } else if (event.key === 'ArrowUp' && items.length) {
        event.preventDefault();
        setExpanded(true);
        setActive(activeIndex <= 0 ? items.length - 1 : activeIndex - 1);
      } else if (event.key === 'Escape') {
        event.preventDefault();
        setExpanded(false);
      } else if (event.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
        event.preventDefault();
        var href = safeUrl(items[activeIndex].href, true);
        if (href) window.location.assign(href);
      }
    });

    results.addEventListener('mousemove', function (event) {
      var item = event.target.closest('[data-itk-live-search-option]');
      if (!item) return;
      var items = options();
      setActive(items.indexOf(item));
    });

    document.addEventListener('pointerdown', function (event) {
      if (!root.contains(event.target)) setExpanded(false);
    });

    form.addEventListener('submit', function () {
      if (controller) controller.abort();
    });
  }

  function enhanceAll() {
    document.querySelectorAll('[data-itk-live-search]').forEach(enhance);
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
