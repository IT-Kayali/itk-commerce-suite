# Search & Filter Live Product Search

The Search & Filter module provides progressive product discovery inside the public catalog toolbar. The live suggestion layer consumes WooCommerce's public Store API instead of introducing a second IT-Kayali product search backend.

## Baseline / no-JavaScript behavior

The rendered control is a normal WordPress/WooCommerce product search form:

```text
GET /?s=<term>&post_type=product
```

If JavaScript is disabled, the Store API is unavailable, or the user submits without selecting a suggestion, the normal product search remains available.

## Live scopes

After the bounded minimum character count is reached, the browser may request:

- `wc/store/v1/products?search=<term>` for product-name/catalog search;
- `wc/store/v1/products?sku=<term>` for optional exact SKU matching;
- `wc/store/v1/products/categories?search=<term>` for optional category suggestions.

SKU results are merged before ordinary product-name results so a full SKU is easy to reach. Duplicate product IDs are removed and all result groups are bounded by configured limits.

The following public filter controls the bounded defaults before they are localized to the browser:

```php
add_filter( 'itk_commerce_live_search_options', function ( $options ) {
    $options['min_chars']       = 2;
    $options['product_limit']   = 6;
    $options['category_limit']  = 4;
    $options['show_categories'] = true;
    $options['sku_matching']    = true;
    $options['debounce_ms']     = 180;
    return $options;
} );
```

Core bounds remain enforced after the filter (minimum characters, result counts and debounce interval).

## Accessibility

The input uses the ARIA combobox/listbox pattern:

- `role="combobox"`;
- `aria-autocomplete="list"`;
- `aria-expanded` tracks panel visibility;
- `aria-controls` references the result listbox;
- `aria-activedescendant` tracks the currently highlighted option;
- Arrow Down / Arrow Up move through results;
- Escape closes results;
- Enter on an active result follows that result;
- normal form submission remains available when no result is active;
- a polite live region announces loading, errors and the available result count.

IME composition events are respected so Arabic and other composition-based input is not queried mid-composition.

## Request lifecycle and cache

- Input is debounced.
- A new search aborts the previous in-flight request using `AbortController`.
- Product-name, SKU and category scopes use `Promise.allSettled()` so one unavailable scope does not discard successful public API results.
- Suggestions are cached in a bounded 30-term in-memory browser cache for the current document.
- An async catalog toolbar replacement is re-enhanced via the public `itk:catalog-updated` event.

## Rendering / security

- Store API navigation URLs are validated before use.
- Product/category names and metadata are inserted with DOM `textContent`, not executable remote HTML.
- The live-search code does not register a custom public product REST route or `admin-ajax.php` query.
- WooCommerce's Store API remains responsible for public product data and visibility behavior.
- The normal product GET search is the fallback if live suggestions fail.

## Responsive presentation

Desktop results open below the search field. On small screens the result surface is viewport-bounded and uses safe-area-aware bottom spacing, while the search form itself remains part of the catalog toolbar.

Browser regression tests cover product/category suggestions, exact SKU priority, keyboard/ARIA state, repeated-term browser caching and mobile horizontal overflow.
