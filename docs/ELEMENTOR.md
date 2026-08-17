# Elementor Theme Builder Compatibility

The Commerce Theme supports Elementor Theme Builder Header and Footer templates without making Elementor a required dependency.

## Registered locations

The Theme registers the standard Elementor Theme Builder locations:

- `header`
- `footer`

Registration happens through Elementor's public `elementor/theme/register_locations` hook. The supported default list can be filtered with `itk_commerce_elementor_theme_locations`.

## Safe fallback

`header.php` and `footer.php` call `elementor_theme_do_location()` only when that public Elementor function exists and the location is registered. When Elementor is unavailable or no Theme Builder template matches, the normal IT-Kayali Header/Footer model renders unchanged.

The `itk_commerce_elementor_location_enabled` filter can disable Elementor replacement for a specific request/location.

## WooCommerce boundary

The Theme intentionally registers only Header and Footer by default. WooCommerce Shop/Product/Cart/Checkout page models, product cards, mini-cart and My Account components remain owned by Commerce Suite contracts, avoiding accidental replacement of WooCommerce data flows or private block internals.

Advanced integrations may deliberately register additional locations, but they must also integrate them into an appropriate Theme rendering point and preserve the Commerce Suite compatibility contracts.

## Mobile navigation

The mobile bottom navigation remains Theme-owned and is rendered after the Footer location even when an Elementor Footer template is active.
