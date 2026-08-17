(function () {
  'use strict';

  function slug(value) {
    return String(value || '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9_-]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function defaults(type) {
    var map = {
      taxonomy: { label: 'Attribute', id: 'attribute', query: 'filter_attribute', display: 'checkbox' },
      price: { label: 'Price', id: 'price', query: 'filter_price', display: 'range' },
      stock: { label: 'Availability', id: 'stock', query: 'filter_stock', display: 'radio' },
      sale: { label: 'On sale', id: 'sale', query: 'filter_sale', display: 'toggle' },
      rating: { label: 'Rating', id: 'rating', query: 'filter_rating', display: 'radio' }
    };
    return map[type] || map.taxonomy;
  }

  function renumber(container) {
    Array.from(container.querySelectorAll('[data-itk-filter-row]')).forEach(function (row, index) {
      row.querySelectorAll('[name]').forEach(function (field) {
        field.name = field.name.replace(/definitions\[[^\]]+\]/, 'definitions[' + index + ']');
      });
      var order = row.querySelector('[data-itk-filter-order]');
      if (order && (!order.value || Number(order.value) === 100)) {
        order.value = String((index + 1) * 10);
      }
    });
  }

  function updateType(row) {
    var select = row.querySelector('[data-itk-filter-type]');
    var type = select ? select.value : 'taxonomy';
    row.dataset.filterType = type;
    row.querySelectorAll('[data-itk-taxonomy-field], [data-itk-taxonomy-option]').forEach(function (field) {
      field.hidden = type !== 'taxonomy';
    });
  }

  function setRowDefaults(row, type, index) {
    var preset = defaults(type);
    var label = row.querySelector('[data-itk-filter-label]');
    var id = row.querySelector('[name$="[id]"]');
    var query = row.querySelector('[name$="[query_key]"]');
    var typeSelect = row.querySelector('[data-itk-filter-type]');
    var display = row.querySelector('[data-itk-display]');
    var order = row.querySelector('[data-itk-filter-order]');

    if (label) label.value = preset.label;
    if (id) id.value = preset.id + (type === 'taxonomy' && index > 0 ? '-' + (index + 1) : '');
    if (query) query.value = preset.query + (type === 'taxonomy' && index > 0 ? '_' + (index + 1) : '');
    if (typeSelect) typeSelect.value = type;
    if (display) display.value = preset.display;
    if (order) order.value = String((index + 1) * 10);

    var title = row.querySelector('[data-itk-filter-row-title]');
    if (title) title.textContent = preset.label;
    updateType(row);
  }

  function init() {
    var builder = document.querySelector('[data-itk-filter-builder]');
    if (!builder) return;

    var rows = builder.querySelector('[data-itk-filter-rows]');
    var template = builder.querySelector('[data-itk-filter-template]');
    if (!rows || !template) return;

    builder.addEventListener('click', function (event) {
      var add = event.target.closest('[data-itk-add-filter]');
      if (add) {
        event.preventDefault();
        var wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(rows.children.length));
        var row = wrapper.querySelector('[data-itk-filter-row]');
        if (!row) return;
        rows.appendChild(row);
        setRowDefaults(row, add.dataset.itkAddFilter || 'taxonomy', rows.children.length - 1);
        renumber(rows);
        var label = row.querySelector('[data-itk-filter-label]');
        if (label) label.focus();
        return;
      }

      var remove = event.target.closest('[data-itk-remove-filter]');
      if (remove) {
        event.preventDefault();
        var removeRow = remove.closest('[data-itk-filter-row]');
        if (removeRow) removeRow.remove();
        renumber(rows);
        return;
      }

      var move = event.target.closest('[data-itk-move-filter]');
      if (move) {
        event.preventDefault();
        var moveRow = move.closest('[data-itk-filter-row]');
        if (!moveRow) return;
        if (move.dataset.itkMoveFilter === 'up' && moveRow.previousElementSibling) {
          rows.insertBefore(moveRow, moveRow.previousElementSibling);
        } else if (move.dataset.itkMoveFilter === 'down' && moveRow.nextElementSibling) {
          rows.insertBefore(moveRow.nextElementSibling, moveRow);
        }
        renumber(rows);
      }
    });

    builder.addEventListener('change', function (event) {
      if (event.target.matches('[data-itk-filter-type]')) {
        var row = event.target.closest('[data-itk-filter-row]');
        if (row) updateType(row);
      }
    });

    builder.addEventListener('input', function (event) {
      if (event.target.matches('[data-itk-filter-label]')) {
        var row = event.target.closest('[data-itk-filter-row]');
        if (!row) return;
        var title = row.querySelector('[data-itk-filter-row-title]');
        if (title) title.textContent = event.target.value || 'New filter';

        var id = row.querySelector('[name$="[id]"]');
        var query = row.querySelector('[name$="[query_key]"]');
        if (id && !id.dataset.touched && !id.value) id.value = slug(event.target.value);
        if (query && !query.dataset.touched && !query.value) query.value = 'filter_' + slug(event.target.value).replace(/-/g, '_');
      }
      if (event.target.matches('[name$="[id]"], [name$="[query_key]"]')) {
        event.target.dataset.touched = '1';
      }
    });

    Array.from(rows.querySelectorAll('[data-itk-filter-row]')).forEach(updateType);
    renumber(rows);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
