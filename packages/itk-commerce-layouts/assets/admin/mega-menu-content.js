(function () {
  'use strict';

  function refreshBlock(block) {
    var select = block.querySelector('[data-itk-block-type]');
    if (!select) {
      return;
    }

    var type = select.value || '';
    block.querySelectorAll('[data-itk-block-fields]').forEach(function (fields) {
      fields.classList.toggle('is-active', fields.getAttribute('data-itk-block-fields') === type);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-itk-mega-block]').forEach(function (block) {
      refreshBlock(block);
      var select = block.querySelector('[data-itk-block-type]');
      if (select) {
        select.addEventListener('change', function () {
          refreshBlock(block);
        });
      }
    });
  });
}());
