(function () {
  'use strict';

  var form = null;
  var activeArea = 'shop';
  var updateTimer = null;

  var queryMap = {
    shop: {
      columns: 'itk_shop_columns',
      sidebar_position: 'itk_shop_sidebar_position',
      density: 'itk_shop_density'
    },
    product: {
      gallery_width: 'itk_product_gallery_width',
      sticky_summary: 'itk_product_sticky_summary',
      tabs_layout: 'itk_product_tabs_layout'
    },
    cart: {
      sticky_totals: 'itk_cart_sticky_totals',
      density: 'itk_cart_density'
    },
    checkout: {
      sticky_summary: 'itk_checkout_sticky_summary',
      content_width: 'itk_checkout_content_width',
      field_density: 'itk_checkout_field_density'
    }
  };

  function activePanel() {
    return form ? form.querySelector('[data-itk-commerce-panel="' + activeArea + '"]') : null;
  }

  function targetFor(area) {
    if (!form) {
      return '';
    }
    var key = 'preview' + area.charAt(0).toUpperCase() + area.slice(1);
    return form.dataset[key] || '';
  }

  function activeModel(panel) {
    var checked = panel ? panel.querySelector('[data-itk-commerce-model]:checked') : null;
    return checked ? checked.value : '';
  }

  function appendOptions(url, panel) {
    var mapping = queryMap[activeArea] || {};
    Object.keys(mapping).forEach(function (option) {
      var field = panel.querySelector('[data-itk-preview-option="' + option + '"]');
      if (!field) {
        return;
      }

      var value = field.type === 'checkbox' ? (field.checked ? '1' : '0') : field.value;
      url.searchParams.set(mapping[option], value);
    });
  }

  function updatePreview() {
    if (!form) {
      return;
    }

    var frame = form.querySelector('[data-itk-commerce-preview]');
    var status = form.querySelector('[data-itk-preview-status]');
    var title = form.querySelector('[data-itk-preview-title]');
    var panel = activePanel();
    var target = targetFor(activeArea);

    if (title) {
      var tab = form.querySelector('[data-itk-commerce-tab="' + activeArea + '"]');
      title.textContent = tab ? tab.textContent.trim() + ' preview' : 'Live preview';
    }

    if (!frame || !panel || !target) {
      if (frame) {
        frame.src = 'about:blank';
      }
      if (status) {
        status.textContent = activeArea === 'product'
          ? 'No published product is available for the live product preview yet.'
          : 'This WooCommerce preview destination is not available yet.';
      }
      return;
    }

    var url;
    try {
      url = new URL(target, window.location.origin);
    } catch (error) {
      return;
    }

    url.searchParams.set('itk_layout_preview', '1');
    url.searchParams.set('_itk_preview_nonce', form.dataset.previewNonce || '');
    url.searchParams.set('itk_template_area', activeArea);
    url.searchParams.set('itk_template_model', activeModel(panel));
    appendOptions(url, panel);

    if (status) {
      status.textContent = 'Unsaved model and option changes are previewed securely.';
    }

    frame.src = url.toString();
  }

  function schedulePreview() {
    window.clearTimeout(updateTimer);
    updateTimer = window.setTimeout(updatePreview, 120);
  }

  function activateArea(area) {
    activeArea = area;

    form.querySelectorAll('[data-itk-commerce-tab]').forEach(function (button) {
      var active = button.getAttribute('data-itk-commerce-tab') === area;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    form.querySelectorAll('[data-itk-commerce-panel]').forEach(function (panel) {
      panel.classList.toggle('is-active', panel.getAttribute('data-itk-commerce-panel') === area);
    });

    updatePreview();
  }

  function activateDevice(device) {
    var stage = form.querySelector('[data-itk-template-stage]');
    if (stage) {
      stage.setAttribute('data-device', device);
    }

    form.querySelectorAll('[data-itk-template-device]').forEach(function (button) {
      button.classList.toggle('is-active', button.getAttribute('data-itk-template-device') === device);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    form = document.querySelector('[data-itk-commerce-template-builder]');
    if (!form) {
      return;
    }

    form.querySelectorAll('[data-itk-commerce-tab]').forEach(function (button) {
      button.addEventListener('click', function () {
        activateArea(button.getAttribute('data-itk-commerce-tab'));
      });
    });

    form.querySelectorAll('[data-itk-template-device]').forEach(function (button) {
      button.addEventListener('click', function () {
        activateDevice(button.getAttribute('data-itk-template-device'));
      });
    });

    form.addEventListener('change', function (event) {
      if (event.target.matches('[data-itk-commerce-model], [data-itk-preview-option]')) {
        schedulePreview();
      }
    });

    activateArea('shop');
  });
}());
