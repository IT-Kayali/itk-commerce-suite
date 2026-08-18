# IT-Kayali Commerce Multilingual – Translation Transfer

This document defines the Phase 5 translation interchange foundation for `itk-commerce-multilingual`.

## Purpose

Translation transfer is module-owned and independent from the Theme. It exchanges translation content and bounded workflow metadata without exporting WordPress users, credentials, WooCommerce customer/order data or other secrets.

The public service is available through:

```php
$transfer = apply_filters( 'itk_commerce_translation_transfer', null );
```

## Supported formats

The foundation supports:

- JSON with an explicit `itk-commerce-translations` schema and schema version;
- CSV with stable machine columns;
- XLIFF 1.2 for translation-tool interchange.

`xlf` is accepted as an alias for `xliff` when calling the service.

## Export

```php
$json = $transfer->export(
    'json',
    array(
        'scope'     => 'published', // current|published
        'languages' => array( 'de', 'ar', 'en' ),
    )
);
```

`current` exports the current revision pointer for each translation entry. `published` exports only the revision currently visible to customers.

Exported records contain stable translation identity and interchange metadata:

```text
translation_key
language_code
translation_value
workflow_status
source_hash
revision_no
updated_at
published_at
```

Author/reviewer user IDs are intentionally excluded. Export also contains no passwords, API keys, customer accounts, orders, payments or WooCommerce commercial state.

### JSON envelope

```json
{
  "schema": "itk-commerce-translations",
  "schema_version": 1,
  "package": "itk-commerce-multilingual",
  "version": "0.1.0-dev",
  "scope": "published",
  "generated_at": "2026-08-18T00:00:00+00:00",
  "records": []
}
```

Unknown JSON schema versions are rejected instead of being guessed or silently migrated.

### CSV

CSV requires at least:

```text
translation_key,language_code,translation_value
```

Additional known columns such as workflow status/source hash/revision metadata are accepted. Unknown extra columns are preserved only by the source file; the current importer normalizes the fields it understands.

### XLIFF 1.2

Each target language is exported as its own XLIFF `<file>` group. Stable translation keys use `trans-unit@resname`. The target contains the translated text; IT-Kayali workflow/source-hash metadata is stored as namespaced-style `prop` values inside the XLIFF document.

The XLIFF layer is an interchange foundation, not a replacement for the Commerce Suite review workflow.

## Import preflight

Imports must be analyzed before they can be applied:

```php
$analysis = $transfer->analyze_import( 'json', $payload );
```

The preflight result classifies records as:

- `new`: no existing translation identity;
- `conflict`: the key/language identity already exists with different content;
- `unchanged`: imported value already equals the published value;
- invalid: malformed identity, duplicate key/language in the same file, unsupported schema or unsafe payload.

Limits in transfer schema v1:

- maximum payload: 5 MiB;
- maximum records: 10,000;
- null bytes rejected;
- JSON schema/version validated;
- required CSV columns validated;
- XLIFF DTD/entity declarations rejected before parsing;
- XLIFF XML parsing uses `LIBXML_NONET`;
- XLIFF requires PHP DOM/XML support.

## Safe application

```php
$result = $transfer->import_as_drafts( 'csv', $payload, get_current_user_id() );
```

Import is deliberately **draft-only**:

1. the entire payload is parsed and validated first;
2. any invalid record aborts application;
3. unchanged published values are skipped;
4. new/conflicting values create append-only draft revisions;
5. existing published revisions remain live;
6. normal `draft -> review -> published` workflow remains mandatory.

An import file declaring a record as `published` does **not** publish it automatically. The source status is useful for preview/interchange, but the destination installation remains authoritative for approval.

## Events and extension boundary

The active service is exposed through `itk_commerce_translation_transfer`.

After a successful draft import the module emits:

```text
itk_commerce_translation_imported
```

The future translator/admin UI should call the same service for upload preview, conflict display and import execution instead of duplicating parsers or direct database writes.

## Known foundation boundary

The translation repository currently persists a source hash rather than the complete source string. Exports therefore guarantee the source hash and stable translation key, not reconstruction of the original source sentence. A later source-catalog/scanner layer may enrich interchange files with source text without changing translation identity or the draft-only safety model.
