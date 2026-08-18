(() => {
  'use strict';

  document.querySelectorAll('[data-itk-layout-source-editor]').forEach((editor) => {
    const select = editor.querySelector('[data-itk-layout-source-select]');
    if (!select) return;

    const refresh = () => {
      const source = select.value || 'theme';
      editor.querySelectorAll('[data-itk-source-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.itkSourcePanel !== source;
      });
    };

    select.addEventListener('change', refresh);
    refresh();
  });
})();
