# Architecture

## Objective

IT-Kayali Commerce Suite is a reusable WooCommerce product. The generic product must remain independent of any single customer while allowing customer-specific branding, layouts and enabled capabilities through versioned profiles.

## Layer model

```text
CLIENT PROFILE
Branding, languages, layout rules, modules, contacts
        ↓
IT-KAYALI THEME
UI, design tokens, patterns, responsive layouts
        ↓
IT-KAYALI CORE
Settings, module management, imports/exports, updates, roles
        ↓
INSTALLABLE MODULES
Search/filter, multilingual, documents, badges, layouts, etc.
        ↓
WORDPRESS + WOOCOMMERCE
Customer data and commerce data through supported APIs
```

## Dependency direction

- The theme may depend on WordPress and optional capabilities exposed by the core.
- The core must not depend on a customer profile.
- Optional modules may depend on the core and explicitly declared modules.
- Modules must communicate through documented services, actions, filters or REST interfaces instead of reaching into another module's internal implementation.
- Customer profiles may configure public product APIs but must never modify generic package source code.

## PHP identity

- Namespace: `ITK\\Commerce`
- WordPress-global identifiers: `itk_commerce_...` or another documented `itk_` prefix.
- REST routes, options, cron hooks, database tables and capabilities must use unique IT-Kayali prefixes.

## WooCommerce rules

- Do not edit WordPress or WooCommerce core files.
- Prefer hooks and supported APIs.
- Use WooCommerce CRUD for order data and keep all order integrations compatible with HPOS.
- Template overrides are a last resort for layout requirements and must be tracked for compatibility review.
- Cart and checkout behavior must be designed so both supported block-based and required classic flows can be tested.

## Module contract

Every installable module must eventually declare:

- package version;
- minimum supported environment;
- tested compatibility range;
- required core version;
- module dependencies;
- migrations and schema version where applicable;
- activation/deactivation behavior;
- uninstall data-retention behavior;
- public services, hooks and REST routes;
- test coverage relevant to its responsibilities.

Inactive modules must not enqueue frontend assets, register recurring jobs or execute application logic beyond minimal discovery required by the core.

## Data ownership

- Product settings use versioned WordPress options or purpose-built tables when scale requires them.
- Customer profiles use versioned configuration data and may package approved media/fonts separately.
- WooCommerce business data remains managed through WooCommerce-supported APIs.
- Secrets, credentials and personal data are excluded from standard configuration exports.

## Customer separation

The generic product core contains no hard-coded customer names, domains, products, branding or content. Customer-specific values live in a profile or in the customer's WordPress/WooCommerce data.

The first reference implementation is Al-Lord Sweets, represented only as a customer profile and deployment target.
