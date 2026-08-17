(() => {
  'use strict';

  const builder = document.querySelector('[data-itk-layout-builder]');
  if (!builder) {
    return;
  }

  const tabs = Array.from(builder.querySelectorAll('[data-itk-tab]'));
  const panels = Array.from(builder.querySelectorAll('[data-itk-panel]'));
  const deviceButtons = Array.from(builder.querySelectorAll('[data-itk-device]'));
  const previewStage = builder.querySelector('[data-itk-preview-stage]');
  const previewFrame = builder.querySelector('[data-itk-preview-frame]');
  const mobileEnabled = builder.querySelector('[data-itk-mobile-enabled]');
  let previewTimer = null;

  const setTab = (name) => {
    tabs.forEach((button) => button.classList.toggle('is-active', button.dataset.itkTab === name));
    panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.itkPanel === name));
  };

  tabs.forEach((button) => {
    button.addEventListener('click', () => setTab(button.dataset.itkTab));
  });

  deviceButtons.forEach((button) => {
    button.addEventListener('click', () => {
      deviceButtons.forEach((item) => item.classList.remove('is-active'));
      button.classList.add('is-active');
      if (previewStage) {
        previewStage.dataset.device = button.dataset.itkDevice || 'desktop';
      }
    });
  });

  const checkedValue = (name, fallback) => {
    const field = builder.querySelector(`input[name="${name}"]:checked`);
    return field ? field.value : fallback;
  };

  const refreshPreview = () => {
    if (!previewFrame || !builder.dataset.previewUrl) {
      return;
    }

    const url = new URL(builder.dataset.previewUrl, window.location.href);
    url.searchParams.set('itk_header_model', checkedValue('header_default', 'classic'));
    url.searchParams.set('itk_footer_model', checkedValue('footer_default', 'classic'));
    url.searchParams.set('itk_mobile_bottom', mobileEnabled && mobileEnabled.checked ? '1' : '0');
    url.searchParams.set('_itk_preview_refresh', String(Date.now()));
    previewFrame.src = url.toString();
  };

  const schedulePreview = () => {
    window.clearTimeout(previewTimer);
    previewTimer = window.setTimeout(refreshPreview, 180);
  };

  builder.querySelectorAll('[data-itk-model-radio]').forEach((input) => {
    input.addEventListener('change', schedulePreview);
  });

  if (mobileEnabled) {
    mobileEnabled.addEventListener('change', () => {
      const mobileButton = deviceButtons.find((button) => button.dataset.itkDevice === 'mobile');
      if (mobileButton && previewStage) {
        deviceButtons.forEach((item) => item.classList.remove('is-active'));
        mobileButton.classList.add('is-active');
        previewStage.dataset.device = 'mobile';
      }
      schedulePreview();
    });
  }

  refreshPreview();
})();
