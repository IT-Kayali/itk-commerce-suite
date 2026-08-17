# Development Rules

## Core principle

Every new capability must be modular, encapsulated and backwards-compatible. A feature must not cause unintended side effects in existing modules, data, APIs, layouts or customer profiles.

## Change design

Before implementation, each non-trivial change should define:

1. responsibility and package boundary;
2. dependencies;
3. data/API impact;
4. migration and rollback requirements;
5. feature flag or entitlement needs where applicable;
6. responsive and multilingual implications;
7. test and acceptance criteria.

## Branching

Use short-lived branches from `main`.

Recommended patterns:

- `foundation/...`
- `feature/...`
- `fix/...`
- `refactor/...`
- `docs/...`
- `release/...`

Changes should normally reach `main` through a pull request.

## Commit messages

Use concise conventional prefixes where practical:

- `feat:` new user-facing capability
- `fix:` defect correction
- `refactor:` internal change without behavior change
- `docs:` documentation
- `test:` tests only
- `chore:` tooling or maintenance

## Compatibility

- No direct edits to WordPress or WooCommerce core.
- Avoid undocumented internals when supported APIs exist.
- Keep customer-specific behavior out of generic packages.
- Version configuration schemas and database migrations.
- Migrations must be safe to run repeatedly when feasible.
- Data-changing migrations require an explicit recovery strategy.

## Security

- Check capabilities for privileged actions.
- Use nonces for state-changing WordPress admin actions.
- Validate and sanitize input; escape output according to context.
- Use prepared database queries when direct database access is justified.
- Never commit passwords, API keys, private certificates or customer personal data.
- Uploaded fonts, imports and documents must be validated before processing.

## Performance

- Load assets only where needed.
- Inactive modules must not enqueue assets or run scheduled work.
- Prefer reusable services and cached/indexed data structures for expensive search/filter operations.
- Performance-sensitive features require measurable budgets before release.

## Internationalization and RTL

- User-facing strings must be translatable.
- Layout logic must not assume LTR.
- Prefer logical CSS properties where appropriate.
- Customer language data belongs in the multilingual module/profile, not hard-coded templates.

## Testing expectations

At minimum, relevant changes must consider:

- activation/deactivation/update paths;
- responsive desktop/tablet/mobile behavior;
- WooCommerce cart, checkout and order behavior where affected;
- HPOS compatibility where orders are involved;
- RTL/LTR behavior where UI is affected;
- permissions and security boundaries;
- regression impact on other installed modules.

Critical or high-severity known defects block a production release.
