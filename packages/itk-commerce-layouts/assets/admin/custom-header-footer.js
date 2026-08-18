(() => {
  'use strict';

  document.querySelectorAll('[data-itk-layout-source-editor]').forEach((editor) => {
    const select = editor.querySelector('[data-itk-layout-source-select]');
    if (!select) return;

    const refresh = () => {
      const source = select.value || 'theme';
      editor.querySelectorAll('[data-itk-source-panel]').forEach((panel) => {
        const active = panel.dataset.itkSourcePanel === source;
        panel.hidden = !active;
        panel.querySelectorAll('input, textarea, select').forEach((field) => {
          field.disabled = !active;
        });
      });
    };

    select.addEventListener('change', refresh);
    refresh();
  });
})();
