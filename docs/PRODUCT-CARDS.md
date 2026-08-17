# Product Cards, Badges and Optional Actions

Phase 3 introduces reusable product-card presentation without copying or replacing WooCommerce loop templates.

## Ownership

- The Theme owns product-card models, bounded visual options, CSS and public extension points.
- The Layouts module resolves portable customer-profile configuration and provides an authenticated Shop live preview.
- Optional Wishlist, Compare, Quick View or other business features remain separate installable modules.
- Customer-specific branding/content stays in the active customer profile.

## Product-card models

The Theme currently registers these reusable models:

- `classic`
- `minimal`
- `boxed`
- `overlay`

Use `itk_commerce_product_card_models` to add reusable models. The selected model flows through `itk_commerce_product_card_model`; unknown model identifiers fall back to a registered Theme model.

## Bounded visual options

`itk_commerce_product_card_options` receives the current options before final Theme validation. Supported values are intentionally bounded:

- `image_ratio`: `portrait`, `square`, `landscape`
- `content_order`: `title-price`, `price-title`
- `content_align`: `left`, `center`
- `price_treatment`: `standard`, `emphasis`, `muted`
- `action_treatment`: `button`, `outline`, `text`
- `hover_behavior`: `none`, `lift`, `image-zoom`
- `badge_style`: `pill`, `corner`, `minimal`
- `show_state_badges`: boolean
- `new_days`: 1–365

The Layouts module stores these values in:

`modules.configuration.itk-commerce-layouts.product_card`

This namespace keeps the component configuration isolated from the Shop/Product/Cart/Checkout page-template editor.

The Theme applies the resolved card classes globally, while the CSS targets WooCommerce product-loop markup. This lets Shop, category, related/upsell and shortcode product loops share one presentation contract without changing WooCommerce data flows.

## Badges

WooCommerce keeps ownership of its native Sale flash. The Theme adds portable product-loop surfaces for:

- Sold out
- Featured
- New
- custom module-provided labels

Integrations can modify the normalized badge array through:

```php
add_filter( 'itk_commerce_product_badges', function ( $badges, $product, $context ) {
    $badges['member'] = array(
        'label' => 'Member',
        'class' => 'member',
    );
    return $badges;
}, 10, 3 );
```

Do not place API secrets, tokens or private credentials in customer profiles.

## Optional product-card actions

The Theme does not ship or force Quick View, Wishlist or Compare behavior. When an integration attaches to `itk_commerce_product_card_actions`, the Theme exposes an action slot after WooCommerce's standard add-to-cart action.

```php
add_action( 'itk_commerce_product_card_actions', function ( $product, $context ) {
    // Render an accessible integration-owned action here.
}, 10, 2 );
```

No empty action wrapper is rendered when the hook has no listeners.

## WooCommerce compatibility

The implementation relies on supported WooCommerce loop hooks and native product methods. It does not patch WooCommerce core, replace product loop templates, or reach into private Cart/Checkout block internals. The native Sale flash and standard product link, thumbnail, title, price, rating and add-to-cart callbacks remain WooCommerce-owned.

The optional `price-title` presentation only changes visual order inside WooCommerce's standard product-loop link; it does not move or duplicate WooCommerce callbacks or product data.

## Live preview

Appearance → Product Cards provides an authenticated real-storefront Shop iframe preview. Unsaved model/options are passed through nonce-protected preview query parameters and resolved at high filter priority without persisting preview state.
