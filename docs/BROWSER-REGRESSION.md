# Browser Regression Coverage

The Commerce Suite uses deterministic Chromium fixtures to protect responsive layout, RTL positioning and accessibility behavior without depending on a specific customer site or production database.

## Scope

The browser gate exercises real reusable frontend assets from:

- `itk-commerce-theme` layout/responsive assets;
- `itk-commerce-theme` WooCommerce and Shop/Product/Cart/Checkout model CSS;
- `itk-commerce-layouts` rich Mega-menu CSS and JavaScript.

Fixtures contain no Al-Lord or other customer-specific branding, product data or URLs.

## Layout/navigation checks

The Playwright suite verifies:

1. rich Mega-menu destination links remain normal navigable links while a separate button controls the panel;
2. `aria-expanded` and `aria-controls` stay synchronized with open state;
3. keyboard Enter opens the panel;
4. Escape closes the panel and restores focus to its toggle;
5. clicking outside closes an open panel;
6. desktop Mega-menu and Footer grids render at expected column counts;
7. tablet grids collapse to two columns;
8. mobile grids collapse to one column;
9. mobile bottom navigation becomes visible at the mobile breakpoint;
10. the fixture has no horizontal viewport overflow at 390 px;
11. RTL maps logical `inset-inline-start` positioning to the right edge for aligned panels;
12. skip-link, landmark, unique-ID and `aria-controls` target contracts remain valid;
13. interactive fixture controls/links retain accessible text names.

## Commerce page-model checks

The Commerce template fixture additionally verifies:

1. Shop can render five configured columns on desktop;
2. Shop collapses to two columns on tablet and one column on mobile;
3. right-positioned Shop Sidebar ordering is applied at desktop and the shell collapses on tablet;
4. mobile Shop output stays within the viewport;
5. Product `gallery-right` presents summary before gallery on desktop;
6. Product model collapses to one column on tablet;
7. sticky Product summary is desktop-only;
8. classic Cart `split` is two columns on desktop and one on tablet;
9. classic sticky Cart totals are disabled at the tablet breakpoint;
10. Cart Block `compact` presentation is expressed through the public IT-Kayali outer shell width only;
11. classic Checkout `split` is two columns on desktop and returns to normal flow on tablet;
12. classic sticky order review is desktop-only;
13. Checkout Block `focused`/boxed presentation is expressed through the public outer shell width and returns to full width on tablet.

No browser test depends on WooCommerce Cart/Checkout private child classes. The deterministic block fixture represents only the IT-Kayali public outer-shell contract.

## CI gate

The `browser-regression` GitHub Actions job runs after static validation and before installable package ZIPs are built.

The job:

1. uses Node.js 20;
2. installs the pinned `@playwright/test` development dependency;
3. installs Chromium and its CI dependencies;
4. starts a local static HTTP server through `playwright.config.js`;
5. runs all responsive/RTL/accessibility and Commerce page-model specs;
6. uploads the Playwright HTML report and failure traces/screenshots when the job fails.

The build job depends on both `validate` and `browser-regression`, so development ZIP artifacts are not produced from a revision that fails either gate.

## Fixture strategy

Static fixtures intentionally test public package output rather than a production customer installation. This keeps the regression gate repeatable and fast while protecting generic Theme/Layouts contracts.

A later environment-level test layer may additionally run selected behavior against a real WordPress/WooCommerce test site. That environment test must remain separate from customer production data and must not replace deterministic package-level checks.

## Extending the suite

New layout/navigation/commerce models should add regression coverage when they introduce a new responsive structure, direction-sensitive positioning rule, keyboard interaction or ARIA contract. Tests should prefer stable semantic roles/classes and public extension behavior over customer copy, WooCommerce private component classes or pixel-perfect screenshots.
