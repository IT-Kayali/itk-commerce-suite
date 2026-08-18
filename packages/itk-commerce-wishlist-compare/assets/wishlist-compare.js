(() => {
  'use strict';

  const config = window.ITKWishlistCompare || {};
  const storageKey = (type) => `itk-commerce-${type}`;
  const read = (type) => {
    try {
      const value = JSON.parse(localStorage.getItem(storageKey(type)) || '[]');
      return Array.isArray(value) ? [...new Set(value.map(Number).filter(Number.isInteger).filter((id) => id > 0))] : [];
    } catch (error) {
      return [];
    }
  };
  const write = (type, ids) => localStorage.setItem(storageKey(type), JSON.stringify(ids));

  const refreshButtons = () => {
    ['wishlist', 'compare'].forEach((type) => {
      const ids = read(type);
      document.querySelectorAll(`[data-itk-${type}-toggle]`).forEach((button) => {
        const id = Number(button.getAttribute(`data-itk-${type}-toggle`));
        const active = ids.includes(id);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.classList.toggle('is-active', active);
      });
    });
  };

  const toggle = (type, id) => {
    const ids = read(type);
    const next = ids.includes(id) ? ids.filter((value) => value !== id) : [...ids, id].slice(type === 'compare' ? -6 : -100);
    write(type, next);
    refreshButtons();
    renderLists();
  };

  document.addEventListener('click', (event) => {
    const wishlist = event.target.closest('[data-itk-wishlist-toggle]');
    const compare = event.target.closest('[data-itk-compare-toggle]');
    if (wishlist) {
      event.preventDefault();
      toggle('wishlist', Number(wishlist.dataset.itkWishlistToggle));
    } else if (compare) {
      event.preventDefault();
      toggle('compare', Number(compare.dataset.itkCompareToggle));
    }
  });

  async function fetchProducts(ids) {
    if (!ids.length || !config.endpoint) return [];
    const url = new URL(config.endpoint, window.location.origin);
    url.searchParams.set('include', ids.join(','));
    url.searchParams.set('per_page', String(Math.min(ids.length, 100)));
    const response = await fetch(url.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } });
    if (!response.ok) throw new Error('Product request failed');
    return response.json();
  }

  const escapeHtml = (value) => String(value || '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));

  async function renderContainer(container) {
    const type = container.dataset.itkSavedProducts;
    const ids = read(type);
    if (!ids.length) {
      container.textContent = config.labels?.empty || 'No products saved yet.';
      return;
    }
    container.setAttribute('aria-busy', 'true');
    try {
      const products = await fetchProducts(ids);
      const byId = new Map(products.map((product) => [Number(product.id), product]));
      const ordered = ids.map((id) => byId.get(id)).filter(Boolean);
      if (type === 'compare') {
        container.innerHTML = `<div class="itk-compare-grid">${ordered.map((product) => `<article class="itk-compare-card"><a href="${escapeHtml(product.permalink)}"><strong>${escapeHtml(product.name)}</strong></a><div>${product.prices?.price ? escapeHtml(product.prices.price) : ''}</div><button type="button" data-itk-compare-toggle="${Number(product.id)}">${escapeHtml(config.labels?.remove || 'Remove')}</button></article>`).join('')}</div>`;
      } else {
        container.innerHTML = `<ul>${ordered.map((product) => `<li><a href="${escapeHtml(product.permalink)}">${escapeHtml(product.name)}</a> <button type="button" data-itk-wishlist-toggle="${Number(product.id)}">${escapeHtml(config.labels?.remove || 'Remove')}</button></li>`).join('')}</ul>`;
      }
    } catch (error) {
      container.textContent = config.labels?.empty || 'No products saved yet.';
    } finally {
      container.removeAttribute('aria-busy');
      refreshButtons();
    }
  }

  function renderLists() {
    document.querySelectorAll('[data-itk-saved-products]').forEach((container) => renderContainer(container));
  }

  refreshButtons();
  renderLists();
})();
