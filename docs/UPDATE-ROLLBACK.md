# Update and Rollback Contract

This document defines the minimum update and rollback behavior for IT-Kayali Commerce Suite packages.

## 1. Release identity

Every installable package must expose a semantic product version. Any package that owns persistent data must also expose a separate schema version.

A release record must identify:

- package name;
- package version;
- schema version where applicable;
- minimum supported PHP, WordPress and WooCommerce versions once verified;
- tested-up-to versions once verified;
- dependency constraints;
- migration requirements;
- rollback limitations;
- changelog.

Compatibility claims must be based on completed tests. Unknown version ranges remain explicitly unset instead of being guessed.

## 2. Pre-update gate

Before a production update, the updater must be able to determine whether:

1. the target package is compatible with the current environment;
2. required package dependencies are installed and compatible;
3. required migrations are available;
4. a recoverable backup exists for code and configuration;
5. any irreversible data migration has an explicitly documented recovery path.

The update must be blocked when a declared hard dependency is missing or incompatible.

## 3. Code update

Package code updates are treated as atomic release replacements as far as the hosting environment allows.

The intended release flow is:

1. download the release artifact from the controlled IT-Kayali distribution source;
2. verify package identity and checksum;
3. create a backup of the currently installed package;
4. stage the replacement package;
5. replace package code;
6. run required migrations;
7. run a health check;
8. mark the update successful only after the health check completes.

A failed code replacement must restore the previous package whenever technically possible.

## 4. Configuration update

Customer profiles and Core settings are versioned independently from executable code.

Configuration imports must:

- validate the schema before persistence;
- reject unsupported future schema versions;
- exclude credentials and personal data from normal portable exports;
- preserve the previous configuration snapshot before applying a replacement;
- allow restoration of the previous snapshot when the new configuration fails validation or acceptance.

## 5. Data migrations

Database or persistent-option migrations must be:

- versioned;
- repeatable or safely idempotent;
- scoped to the owning package;
- capable of detecting whether they have already completed;
- tested against interrupted execution;
- documented with forward and recovery behavior.

A migration must not directly modify WordPress or WooCommerce Core database internals outside supported APIs.

WooCommerce order data must be accessed through supported WooCommerce APIs so HPOS remains a valid storage mode.

## 6. Rollback levels

### Level A — Code rollback

Restore the previously installed package code when no incompatible persistent-data migration prevents it.

### Level B — Configuration rollback

Restore the previous versioned Core settings or customer profile snapshot.

### Level C — Data recovery

Restore from a verified backup when a migration changed persistent data in a way that cannot safely be reversed by code.

A UI label such as “one-click rollback” must never imply that an irreversible data migration can be undone without a valid recovery method.

## 7. Module isolation

An update to one optional module must not silently rewrite unrelated module settings or activate/deactivate unrelated modules.

Inactive modules must not run frontend logic or background processes merely because another package was updated.

Module dependency changes require explicit compatibility validation before activation of the new release.

## 8. Customer separation

Generic package updates must not contain customer-specific names, branding, products, media, credentials or production business data.

Customer-specific changes are delivered through the versioned customer profile or the customer's own WordPress/WooCommerce content and configuration.

## 9. Failed update behavior

When an update fails:

1. stop further migrations for the failed package;
2. preserve diagnostic information without exposing secrets;
3. restore code when safe;
4. restore configuration when needed;
5. require backup recovery if the data migration is not safely reversible;
6. keep unrelated packages operational whenever possible.

## 10. Production release gate

A stable release cannot be promoted until:

- automated validation passes;
- install/update/deactivate/reactivate tests pass;
- package ZIP artifacts are reproducible from the repository state;
- the compatibility matrix is updated from real test results;
- migrations and rollback notes are documented;
- there are no unresolved critical or high-severity defects for the release scope.

For the Al-Lord rollout, this contract supplements the separate final migration/cutover procedure. Production WooCommerce data is protected by the migration plan and must not be treated as disposable development configuration.
