# Phase 0 – Environment and migration audit

This audit must be filled from the real source shop and the real target/staging installation. Unknown values remain `TBD`; they must not be guessed from repository source.

## Source shop inventory

| Item | Value |
| --- | --- |
| Shop URL | TBD |
| WordPress version | TBD |
| PHP version | TBD |
| WooCommerce version | TBD |
| Database engine/version | TBD |
| Active theme + version | TBD |
| Elementor / Elementor Pro version | TBD |
| HPOS status | TBD |
| Cart/Checkout Blocks status | TBD |
| Cron implementation | TBD |
| Object/page cache | TBD |
| CDN/proxy | TBD |

## Business-critical plugins

Record exact plugin name, version, purpose and whether it writes order/product/customer data.

| Plugin | Version | Purpose | Data impact | Target decision |
| --- | --- | --- | --- | --- |
| TBD | TBD | TBD | TBD | keep/replace/remove |

Special attention: payment gateways, shipping, tax, SMTP/email, invoicing, feeds, SEO, caching, security, product add-ons, subscriptions and multilingual plugins.

## WooCommerce data volume

| Object | Count |
| --- | ---: |
| Products | TBD |
| Variations | TBD |
| Orders | TBD |
| Refunds | TBD |
| Customers | TBD |
| Coupons | TBD |
| Product categories | TBD |
| Attributes/terms | TBD |
| Media attachments | TBD |

Also record the highest current order ID/order number and the newest order timestamp before every migration rehearsal.

## Target/staging inventory

| Item | Value |
| --- | --- |
| Target URL | TBD |
| WordPress version | TBD |
| PHP version | TBD |
| WooCommerce version | TBD |
| Database engine/version | TBD |
| HPOS status | TBD |
| Filesystem/SSH access | TBD |
| Database backup/restore access | TBD |
| SMTP/test-mail access | TBD |

## Compatibility matrix

For every Commerce Suite package, record installed version, activation result and the concrete WordPress/WooCommerce/Elementor versions tested in this environment. Update each package `compatibility.json` only after the environment is actually verified.

## Migration risks

- source shop continues receiving orders while design/development occurs;
- HPOS synchronization state may differ between source and target;
- payment gateway tokens/webhook configuration may be environment-specific;
- generated documents/emails can contain customer data and must never be committed to Git;
- order/customer/product IDs must remain stable where integrations depend on them;
- imported configuration must not contain secrets.

## Required backups before any write migration

1. source database dump;
2. source `wp-content`/uploads backup;
3. target database dump;
4. target files backup;
5. exported Commerce Suite customer profile;
6. list of active plugins/themes and versions.

## Acceptance criteria for completing Phase 0

Phase 0 is complete only when all `TBD` fields needed for the rollout have real values, business-critical plugins have a target decision, data counts have been captured, backup/restore has been tested, and the rollback owner/window is known.
