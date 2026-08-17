(function () {
  'use strict';

  var form = null;
  var updateTimer = null;

  var queryMap = {
    image_ratio: 'itk_card_image_ratio',
    content_align: 'itk_card_content_align',
    price_treatment: 'itk_card_price_treatment',
    action_treatment: 'itk_card_action_treatment',
    hover_behavior: 'itk_card_hover_behavior',
    badge_style: 'itk_card_badge_style',
    show_state_badges: 'itk_card_show_state_badges',
    new_days: 'itk_card_new_days'
  };

  function updatePreview() {
    if (!form) {
      return;
    }

    var frame = form.querySelector('[data-itk-card-preview]');
    var status = form.querySelector('[data-itk-card-preview-status]');
    var target = form.dataset.previewUrl || '';

    if (!frame || !target) {
      if (frame) {
        frame.src = 'about:blank';
      }
      if (status) {
        status.textContent = 'The WooCommerce Shop preview destination is not available yet.';
      }
      return;
    }

    var url;
    try {
      url = new URL(target, window.location.origin);
    } catch (error) {
      return;
    }

    var model = form.querySelector('[data-itk-card-model]:checked');
    url.searchParams.set('itk_layout_preview', '1');
    url.searchParams.set('_itk_preview_nonce', form.dataset.previewNonce || '');
    url.searchParams.set('itk_template_area', 'product-card');
    url.searchParams.set('itk_product_card_model', model ? model.value : 'classic');

    Object.keys(queryMap).forEach(function (option) {
      var field = form.querySelector('[data-itk-card-option="' + option + '"]');
      if (!field) {
        return;
      }
      var value = field.type === 'checkbox' ? (field.checked ? '1' : '0') : field.value;
      url.searchParams.set(queryMap[option], value);
    });

    if (status) {
      status.textContent = 'Unsaved card changes are previewed securely on the real storefront.';
    }
    frame.src = url.toString();
  }

  function schedulePreview() {
    window.clearTimeout(updateTimer);
    updateTimer = window.setTimeout(updatePreview, 120);
  }

  function activateDevice(device) {
    var stage = form.querySelector('[data-itk-card-stage]');
    if (stage) {
      stage.setAttribute('data-device', device);
    }

    form.querySelectorAll('[data-itk-card-device]').forEach(function (button) {
      button.classList.toggle('is-active', button.getAttribute('data-itk-card-device') === device);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    form = document.querySelector('[data-itk-product-card-builder]');
    if (!form) {
      return;
    }

    form.querySelectorAll('[data-itk-card-device]').forEach(function (button) {
      button.addEventListener('click', function () {
        activateDevice(button.getAttribute('data-itk-card-device'));
      });
    });

    form.addEventListener('change', function (event) {
      if (event.target.matches('[data-itk-card-model], [data-itk-card-option]')) {
        schedulePreview();
      }
    });

    updatePreview();
  });
}());
