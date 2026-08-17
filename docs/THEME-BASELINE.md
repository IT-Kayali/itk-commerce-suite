# Theme baseline 0.1.0-dev

This document defines what “base template ready” means for the first IT-Kayali Commerce theme milestone.

## Included

- installable WordPress theme package structure;
- reusable `header.php` and `footer.php`;
- page, front-page, single, archive, search and 404 templates;
- WooCommerce fallback template and wrapper integration;
- WooCommerce gallery support;
- primary, secondary, mobile, mobile-bottom and footer menu locations;
- mobile drawer navigation;
- persistent mobile bottom navigation fallback with Home, Shop, Cart and Account;
- AJAX-updating cart quantity badge;
- four footer widget columns and shop-sidebar registration;
- `theme.json` design tokens and CSS custom-property tokens;
- responsive CSS for desktop, tablet and smartphone;
- logical CSS properties and RTL compatibility layer;
- local-font policy with no remote Google Fonts by default;
- accessible skip link, focus styles, keyboard Escape behavior and reduced-motion handling;
- layered CSS/JS loading so later modules can remain isolated.

## Deliberately not hard-coded

The generic theme contains no Al-Lord name, logo, colors, product data, contact information or customer-specific layout decisions.

## Separate modules from the approved plan

The following are not collapsed into the base theme and remain separate packages by architecture decision:

- Header/Footer/Layout Builder advanced models;
- multilingual content and translation data;
- advanced AJAX search/filter builder;
- invoices, delivery notes, returns and packing lists;
- badge management rules;
- Elementor widgets/theme locations;
- wishlist/compare;
- gift-box builder;
- controlled code manager.

## Acceptance before merge

- PHP syntax validation passes in GitHub Actions.
- `theme.json` parses successfully.
- development ZIP artifacts are produced.
- no customer data or secrets are present.
- pull request remains mergeable.

This baseline is the reusable template that later modules and customer profiles extend.
