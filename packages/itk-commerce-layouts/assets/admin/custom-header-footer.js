(() => {
  'use strict';

  const splitBundle = (value) => {
    const css = [];
    const js = [];
    let html = String(value || '');

    html = html.replace(/<style\b[^>]*>([\s\S]*?)<\/style\s*>/gi, (_match, code) => {
      css.push(String(code || '').trim());
      return '';
    });

    html = html.replace(/<script\b[^>]*>([\s\S]*?)<\/script\s*>/gi, (_match, code) => {
      js.push(String(code || '').trim());
      return '';
    });

    return {
      html: html.trim(),
      css: css.filter(Boolean).join('\n\n'),
      js: js.filter(Boolean).join('\n\n'),
    };
  };

  const composeBundle = (html, css, js) => {
    const parts = [];
    const markup = String(html || '').trim();
    const styles = String(css || '').trim();
    const scripts = String(js || '').trim();

    if (markup) parts.push(markup);
    if (styles) parts.push(`<style>\n${styles}\n</style>`);
    if (scripts) parts.push(`<script>\n${scripts}\n</script>`);

    return parts.join('\n\n');
  };

  document.querySelectorAll('[data-itk-layout-source-editor]').forEach((editor) => {
    const select = editor.querySelector('[data-itk-layout-source-select]');
    if (!select) return;

    const customPanel = editor.querySelector('[data-itk-source-panel="custom_html"]');
    if (customPanel) {
      const shared = customPanel.querySelector('textarea[name$="[html][shared]"]');
      const css = customPanel.querySelector('textarea[name$="[html][css]"]');
      const js = customPanel.querySelector('textarea[name$="[html][js]"]');
      const tablet = customPanel.querySelector('textarea[name$="[html][tablet]"]');
      const mobile = customPanel.querySelector('textarea[name$="[html][mobile]"]');

      if (shared && css && js) {
        const description = customPanel.querySelector('h3 + p');
        if (description) {
          description.textContent = 'Füge deinen kompletten Header- oder Footer-Code in ein einziges Feld ein. HTML, <style> und <script> werden beim Speichern intern sauber getrennt.';
        }

        const label = document.createElement('label');
        label.className = 'itk-hf-field';

        const title = document.createElement('span');
        title.textContent = 'Kompletter Code (HTML + CSS + JavaScript)';

        const combined = document.createElement('textarea');
        combined.className = 'itk-hf-code--large';
        combined.spellcheck = false;
        combined.value = composeBundle(shared.value, css.value, js.value);
        combined.setAttribute('data-itk-combined-code', '');

        const hint = document.createElement('small');
        hint.textContent = 'Einfach den vollständigen Code einfügen – inklusive <style> und <script>. Die getrennten Felder werden automatisch im Hintergrund befüllt.';

        label.append(title, combined, hint);

        const deviceGrid = customPanel.querySelector('.itk-hf-device-grid');
        if (deviceGrid) {
          customPanel.insertBefore(label, deviceGrid);
        } else {
          customPanel.appendChild(label);
        }

        [shared, css, js].forEach((field) => {
          const fieldLabel = field.closest('label');
          if (fieldLabel) fieldLabel.hidden = true;
        });

        if (tablet || mobile) {
          const details = document.createElement('details');
          details.className = 'itk-hf-device-overrides';
          const summary = document.createElement('summary');
          summary.textContent = 'Erweitert: separates Tablet-/Mobile-HTML';
          details.appendChild(summary);

          [tablet, mobile].forEach((field) => {
            if (!field) return;
            const fieldLabel = field.closest('label');
            if (fieldLabel) details.appendChild(fieldLabel);
          });

          label.insertAdjacentElement('afterend', details);
          if (deviceGrid && !deviceGrid.querySelector('label:not([hidden])')) {
            deviceGrid.hidden = true;
          }
        }

        const form = editor.closest('form');
        if (form) {
          form.addEventListener('submit', () => {
            const parsed = splitBundle(combined.value);
            shared.value = parsed.html;
            css.value = parsed.css;
            js.value = parsed.js;
          });
        }
      }
    }

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
