# Search & Filter Async Catalog Navigation

The Search & Filter module progressively enhances the existing server-rendered catalog GET flow. JavaScript is optional: the same filter form and filter links continue to work as normal full-page navigation when Fetch/History support is missing or an async request fails.

## Source of truth

The browser does not build a second WooCommerce query implementation. It requests the same same-origin catalog URL that a normal browser navigation would request.

Server responsibilities remain authoritative:

1. `FilterSchema` allow-lists the configured filter types, IDs and public URL keys.
2. `UrlState` parses only configured bounded filter keys.
3. `WooQueryAdapter` extends WooCommerce product query hooks.
4. WooCommerce remains responsible for product visibility and catalog data.
5. `FilterRenderer` produces the accessible GET form and active-filter links.

This also means the async layer can be disabled without changing filtering semantics.

## Browser enhancement

`assets/js/catalog-async.js` activates only when the browser provides Fetch, DOMParser, History, URL, FormData and AbortController and the server-rendered result boundary is present.

It enhances:

- Search & Filter GET form submission;
- active-filter removal;
- clear-all links;
- WooCommerce pagination links after filtering.

The script:

- accepts same-origin URLs only;
- canonicalizes multiple checkbox values into comma-separated filter state;
- canonicalizes progressive `min` / `max` inputs into the existing `min-max` price URL contract;
- cancels stale requests with `AbortController`;
- fetches the full server-rendered catalog response;
- parses the HTML with `DOMParser`;
- replaces only `[data-itk-catalog-toolbar]` and `[data-itk-catalog-results]`;
- updates the document title;
- uses `history.pushState()` for new filter states;
- reacts to `popstate` so Back/Forward restores server-rendered catalog state without a full reload;
- falls back to `window.location.assign()` if the response is invalid or the async request fails.

## Result boundary

`CatalogAsyncNavigation` adds a stable wrapper around WooCommerce loop results and pagination:

```html
<div class="itk-catalog-results" data-itk-catalog-results aria-busy="false">
  <p class="screen-reader-text" role="status" aria-live="polite"></p>
  <!-- WooCommerce loop / no-products message / pagination -->
</div>
```

The wrapper is created through public WooCommerce hooks rather than template copies or core modifications.

## Accessibility and motion

- `aria-busy` represents the loading state.
- A polite live region announces loading/completion using WordPress-translatable strings.
- Existing normal links/forms remain functional without JavaScript.
- Reduced-motion users do not receive the loading opacity transition.

## Security model

This is a read-only public catalog GET flow. It does not create a custom state-changing WordPress AJAX action and does not accept arbitrary query parameters as product-query instructions. The normalized server-side `UrlState` allow-list remains the security/query boundary.

A future state-changing Search & Filter feature must use the appropriate WordPress authentication, capability and nonce mechanisms; this read-only navigation contract must not be reused as authorization for writes.

## Browser event

After a successful replacement the module dispatches:

```js
document.addEventListener('itk:catalog-updated', function (event) {
  console.log(event.detail.url);
});
```

Optional modules may use this event to re-bind purely client-side presentation behavior. They must not mutate Commerce Suite or WooCommerce server data based only on this event.

## Regression coverage

The browser suite verifies that:

- the catalog result changes without a document reload;
- checkbox and price form controls produce the canonical public URL state;
- History receives the filtered URL;
- the browser Back action restores the prior products through async navigation;
- the live status and `aria-busy` state settle correctly;
- the page-level JavaScript state survives, proving that a full navigation did not occur.
