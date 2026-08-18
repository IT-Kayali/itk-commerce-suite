# Al-Lord Reference Profile

This directory contains the first reference-customer configuration for the IT-Kayali Commerce Suite.

Files:

- `profile.json` — portable customer configuration for branding/languages/layout/module enablement. It contains no credentials or production commerce data.
- rollout procedures live in `docs/AL-LORD-ROLLOUT.md`.

Rules:

- Al-Lord-specific branding and configuration belong here, not in generic Theme/Core/modules.
- Production products, customers, orders, media dumps, database exports and generated customer documents must not be committed.
- Secrets, payment credentials, SMTP credentials, private keys and API tokens must not be committed.
- Environment-specific credentials are configured only on the destination installation after migration.
- Deployment must preserve all WooCommerce production data and reconcile orders created while the new design is being built.
- The profile can be versioned independently from reusable package releases.
