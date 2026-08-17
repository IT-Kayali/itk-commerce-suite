# Search & Filter Builder and Progressive UI

This Phase 4 slice adds the first customer-configurable filter builder and a server-rendered catalog filter interface. AJAX is an enhancement layer planned separately; the current filter form works through normal GET requests.

## Admin builder

The module registers **WooCommerce → Search & Filter** for users with the Commerce Suite design-management capability.

The builder edits only the active customer profile namespace:

```text
modules.configuration.itk-commerce-search-filter.filters
```

It supports:

- adding taxonomy/attribute filters;
- adding Price, Availability, Sale and Rating filters;
- editing portable filter IDs and public URL keys;
- product category/tag/brand and WooCommerce `pa_*` attribute taxonomies;
- Checkbox, Radio, Select, Chips, Range and Toggle display choices where valid for the selected type;
- taxonomy multi-selection and `any`/`all` matching;
- result counts metadata;
- collapsed-by-default groups;
- ordering with accessible up/down controls;
- removing filter definitions;
- schema validation before profile persistence.

The schema permits multiple taxonomy/attribute definitions but only one Price, Stock, Sale and Rating definition. Duplicate IDs and duplicate public query keys are rejected by normalization.

## Frontend progressive filter interface

The renderer attaches through the Phase 3 public Theme action:

```text
itk_commerce_catalog_toolbar
```

This activates the Theme toolbar without making Search/Filter a Theme dependency. Native WooCommerce result-count and ordering controls remain intact.

The interface contains:

- a filter trigger with active-group count;
- collapsible filter groups implemented with native `<details>` elements;
- taxonomy Checkbox/Radio/Select/Chips output;
- numeric minimum/maximum price fields;
- Availability Radio/Select/Chips output;
- Sale checkbox/toggle output;
- Rating Radio/Select/Chips output;
- Apply and Clear All controls;
- active-filter chips with clear-one links;
- a normal GET form that works without JavaScript.

## Price form state

The progressive form may submit a price value as:

```text
filter_price[min]=20&filter_price[max]=150
```

`UrlState` accepts this bounded form representation and serializes normalized state back to the canonical shareable value:

```text
filter_price=20-150
```

The future AJAX/history layer will use the canonical serializer when replacing browser history state.

## Empty customer configurations

An explicitly saved empty definitions array is preserved. This differs from an unconfigured profile: an unconfigured profile receives neutral default filters, while a customer who intentionally removes every filter receives no Search/Filter toolbar.

## Responsive and accessibility behavior

The server-rendered interface uses semantic form controls and native details/summary behavior. The browser regression fixture covers:

- bounded desktop panel width;
- two-column desktop filter groups;
- native collapse/expand without JavaScript;
- one-column mobile layout;
- active-filter chip wrapping;
- RTL and horizontal-overflow protection.

## Next workstream

The next Phase 4 slice will enhance this existing form rather than replace it:

1. AJAX result refresh;
2. loading/error states;
3. canonical `history.pushState` / back-forward restoration;
4. mobile off-canvas drawer;
5. live product search/autocomplete;
6. cache/index optimization and invalidation.

The non-JavaScript GET behavior remains the compatibility fallback after AJAX is introduced.
