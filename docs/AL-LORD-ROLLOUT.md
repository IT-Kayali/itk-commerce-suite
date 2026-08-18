# Phase 9 – Al-Lord rollout runbook

The Al-Lord deployment is a reference-customer rollout of the reusable IT-Kayali Commerce Suite. Customer-specific assets/configuration stay under `client-profiles/al-lord/`; reusable package source must remain customer-neutral.

## Principle: design first, commerce data last

The target shop may be built and visually tested while the existing production shop continues taking orders. Product/order/customer data is not treated as static until the final migration window. The final cutover therefore uses a rehearsed full migration or a verified delta migration that includes every order created since the last rehearsal.

## Rehearsal 1 – staging migration

1. Complete `docs/PHASE-0-AUDIT.md` with real source/target values.
2. Create source and target database/files backups.
3. Record source object counts and newest order timestamp/order number.
4. Import/copy production WooCommerce data into the staging target using the selected migration mechanism.
5. Activate Commerce Core, Theme and required modules.
6. Import/activate the Al-Lord customer profile.
7. Re-save permalinks and flush only required caches/rewrite rules.
8. Verify product, variation, category, attribute, coupon, customer, order and refund counts.
9. Run test orders for every critical payment/shipping path available in staging/sandbox.
10. Verify transactional email language, invoices/documents, stock changes, cancellations and refunds.
11. Verify HPOS synchronization/authoritative order storage according to the audited source/target configuration.
12. Record defects; fix reusable defects in packages and customer-only defects in the Al-Lord profile/assets.

## Pre-cutover gate

Do not start the final cutover unless:

- Phase 5 multilingual workflows and browser regression are green;
- all required package CI checks are green;
- Phase 8 hardening checklist is complete;
- staging backup/restore has been proven;
- payment/shipping/email test paths are known;
- source and target order counts can be reconciled;
- DNS/domain/SSL plan is prepared if the target host changes;
- rollback can restore the former production system without data loss.

## Final cutover

1. Announce the maintenance/freeze window if the selected migration method cannot safely copy live deltas.
2. Put the old checkout into maintenance only for the shortest necessary period.
3. Record final source counts, newest order number and timestamp.
4. Take a final database backup and files/uploads snapshot.
5. Apply the final full migration or the rehearsed delta covering all changes since the last copy.
6. Reconcile products, customers, orders, refunds, coupons and stock before opening checkout.
7. Activate the tested Commerce Suite package versions and Al-Lord profile.
8. Reconfigure environment-specific secrets outside Git: payment credentials, webhooks, SMTP, API keys and certificates.
9. Run one real/low-value end-to-end order where operationally possible, then cancel/refund according to the business procedure.
10. Verify confirmation email, stored order language, payment state, stock movement and generated document.
11. Open the shop and monitor application/PHP/WooCommerce logs plus new orders.

## HPOS safeguard

When source or target uses WooCommerce High-Performance Order Storage, never assume synchronization is complete. Before cutover and before decommissioning the former shop, confirm the authoritative order data store, compatibility mode state and synchronization backlog in the actual WooCommerce environment. The repository contains no production order data and cannot prove this state by itself.

## Rollback trigger

Rollback immediately if any of the following cannot be corrected safely inside the agreed cutover window:

- missing/reordered orders or customers;
- payment capture/callback failure;
- incorrect stock changes;
- checkout unavailable for critical customer paths;
- order emails or essential shipping/tax logic broken;
- database integrity or HPOS reconciliation failure.

## Rollback procedure

1. Stop writes to the new checkout.
2. Export/record every order accepted on the new target since opening.
3. Restore the former production endpoint/files/database or switch traffic back according to the hosting plan.
4. Reconcile any orders accepted during the failed window before reopening sales.
5. Preserve logs and the failed target snapshot for diagnosis; do not overwrite evidence with another blind migration.

## Post-go-live observation

For the first operating period, compare incoming order count/revenue/payment states with the payment provider and WooCommerce reports, verify scheduled actions/cron, review failed emails/webhooks, monitor stock, and keep the rollback backup until the business signs off.
