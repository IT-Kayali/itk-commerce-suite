# Browser Regression Coverage

The Commerce Suite uses a deterministic Chromium regression fixture to protect responsive layout, RTL positioning and accessibility behavior without depending on a specific customer site or production database.

## Scope

The browser gate exercises the real reusable frontend CSS and JavaScript from:

- `itk-commerce-theme` layout/responsive assets;
- `itk-commerce-layouts` rich Mega-menu CSS;
- `itk-commerce-layouts` rich Mega-menu JavaScript.

The fixture contains no Al-Lord or other customer-specific branding, product data or URLs.

## Current browser checks

The Playwright suite verifies:

1. rich Mega-menu destination links remain normal navigable links while a separate button controls the panel;
2. `aria-expanded` and `aria-controls` stay synchronized with the open state;
3. keyboard Enter opens the panel;
4. Escape closes the panel and restores focus to its toggle;
5. clicking outside closes an open panel;
6. desktop Mega-menu and Footer grids render at their expected column counts;
7. tablet grids collapse to two columns;
8. mobile grids collapse to one column;
9. mobile bottom navigation becomes visible at the mobile breakpoint;
10. the fixture has no horizontal viewport overflow at 390 px;
11. RTL mode maps logical `inset-inline-start` positioning to the right edge for aligned panels;
12. skip-link, landmark, unique-ID and `aria-controls` target contracts remain valid;
13. interactive fixture controls/links retain accessible text names.

## CI gate

The `browser-regression` GitHub Actions job runs after static validation and before installable package ZIPs are built.

The job:

1. uses Node.js 20;
2. installs the pinned `@playwright/test` development dependency;
3. installs Chromium and its CI dependencies;
4. starts a local static HTTP server through `playwright.config.js`;
5. runs the responsive/RTL/accessibility suite;
6. uploads the Playwright HTML report and failure traces/screenshots when the job fails.

The build job depends on both `validate` and `browser-regression`, so development ZIP artifacts are not produced from a revision that fails the browser gate.

## Fixture strategy

The static fixture intentionally tests public package output rather than a production customer installation. This makes the regression gate repeatable and fast while protecting the generic Theme/Layouts contracts.

A later environment-level test layer may additionally run the same behavior against a real WordPress/WooCommerce test site. That environment test must remain separate from customer production data and must not replace these deterministic package-level checks.

## Extending the suite

New layout or navigation models should add regression coverage when they introduce a new responsive structure, direction-sensitive positioning rule, keyboard interaction or ARIA contract. Tests should prefer stable semantic roles/classes and public extension behavior over customer copy or pixel-perfect screenshots.
