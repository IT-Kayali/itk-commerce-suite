# Core Foundation

This document describes the Phase 1 foundation of `itk-commerce-core`.

## Responsibilities

The Core owns only cross-package infrastructure:

- versioned global suite settings;
- customer-profile persistence and schema validation;
- module registration, dependency validation and ordered boot;
- roles and capabilities;
- activation/deactivation lifecycle coordination;
- public extension hooks used by separately installable modules.

Customer branding, product data and customer-specific behavior do not belong in the Core.

## Module registration

Installed modules implement `ITK\Commerce\Core\Contracts\ModuleInterface` and register an instance on `itk_commerce_register_modules`.

Each module declares:

- stable module ID;
- module version;
- minimum Core/PHP/WordPress/WooCommerce versions when applicable;
- module dependencies;
- its registration routine.

Enabled modules are booted only when their declared environment is compatible and their dependencies are enabled, installed and already booted. Dependency cycles and unresolved dependencies are rejected rather than partially loaded.

## Settings schema

Core option: `itk_commerce_settings`

Current schema version: `1`

The option intentionally contains only cross-package state:

```json
{
  "schema_version": 1,
  "active_profile_id": "",
  "modules": {
    "enabled": []
  }
}
```

Module-specific settings must use their own versioned namespaces.

## Customer profiles

Profile store: `itk_commerce_customer_profiles`

Current profile schema version: `1`

A profile can contain branding, design values, contacts, languages, layout assignments and module configuration. Portable profiles explicitly reject common secret fields such as passwords, API secrets, private keys and access tokens.

The active customer profile may define the authoritative enabled-module list. This keeps customer-specific choices outside generic package source code.

## Roles and capabilities

Core capabilities:

- `itk_manage_commerce`
- `itk_manage_design`
- `itk_manage_modules`
- `itk_manage_profiles`
- `itk_manage_translations`
- `itk_manage_documents`

Foundation roles:

- Commerce Designer
- Commerce Translator
- Commerce Document Manager

Administrators receive all Commerce Suite capabilities. Shop Managers receive operational commerce/profile/document capabilities but not module-level administration by default.

Capabilities and profile data are preserved on deactivation. Permanent deletion will be implemented only as an explicit uninstall choice.

## CI safeguards

The validation workflow currently checks:

- PHP syntax across packages and tests;
- valid `theme.json`;
- Core activation/deactivation lifecycle smoke test;
- absence of the reference-customer name inside generic packages;
- successful creation of installable Theme and Core ZIP artifacts.

Full WordPress/WooCommerce integration tests remain a separate hardening step.
