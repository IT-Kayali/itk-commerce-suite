# Search & Filter Mobile Drawer

The Search & Filter module keeps the server-rendered `<details>` + GET form as its baseline and progressively upgrades it to an off-canvas drawer on viewports up to 760px.

## Progressive behavior

Without JavaScript, the existing filter trigger opens the normal responsive `<details>` panel and the form submits through the canonical catalog GET flow.

With JavaScript and a matching mobile/tablet viewport:

- the existing filter trigger opens a logical-end drawer;
- the existing filter form is moved nowhere and duplicated nowhere;
- a drawer header and close button are added to the existing panel;
- a backdrop is added inside the same filter component;
- the panel receives `role="dialog"` and `aria-modal="true"` only while open;
- focus moves to the close button;
- Tab / Shift+Tab stay inside the open drawer;
- Escape and backdrop click close it;
- focus returns to the original trigger;
- body scrolling is locked while the drawer is open;
- submitting the form releases the drawer before the async catalog toolbar/result replacement occurs.

This keeps one filter definition, one form and one server query implementation across desktop, tablet, mobile, JavaScript and no-JavaScript flows.

## Responsive and RTL contract

The breakpoint is 760px in both the PHP-localized configuration and the drawer stylesheet.

The drawer is positioned with logical CSS properties:

```css
inset-inline-end: 0;
```

LTR therefore opens from the right. RTL opens from the left and uses the opposite off-screen transform direction. Phones up to 480px use the full viewport width; wider small tablets use a maximum 420px drawer.

## Accessibility

- trigger `aria-expanded` follows drawer state;
- trigger `aria-controls` points to the enhanced panel;
- open panel receives dialog semantics;
- close button has a translated accessible label;
- keyboard focus is trapped while open;
- Escape closes and restores focus;
- existing native filter-group `<details>` controls remain keyboard operable;
- reduced-motion preference disables drawer/backdrop transitions.

## Async catalog compatibility

The toolbar is replaced after a successful async catalog update. The drawer listens for the public `itk:catalog-updated` browser event and enhances the newly rendered filter component automatically.

The form submit handler closes the drawer before the Fetch/History layer replaces the toolbar, preventing a stale body scroll lock or stale modal state.

## Security and data boundaries

The drawer is presentation only. It does not create new filter parameters, AJAX endpoints, queries, database writes or customer data. `FilterSchema`, `UrlState`, `WooQueryAdapter` and WooCommerce remain authoritative for catalog behavior.
