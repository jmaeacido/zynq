# ZYNQ Phase 7 Status

## Scope Completed

- Added simple tenant licensing management with license key, status, subscription expiry, grace period, and active/disabled controls.
- Enforced disabled, suspended, inactive, and expired-past-grace tenants through tenant middleware while preserving Super Admin access.
- Added tenant-facing warning banners for expired/grace/blocked license states.
- Added onboarding wizard pages for tenant setup progress, branch setup, and terminal setup.
- Added BIR information setup screen for TIN, taxpayer type, RDO code, registered address, and registration notes.
- Added invoice branding setup for trade name, optional logo upload, and per-tenant invoice footer.
- Added invoice preview generator with sample invoice output and configured tenant/branch/terminal fields.
- Updated compliance checklist with the required disclaimer: “BIR-ready, not automatically BIR-approved.”
- Added audit persistence for licensing, onboarding completion, BIR info changes, invoice settings changes, invoice previews, and compliance checklist viewing.

## License Rules

- License enforcement is intentionally simple and maintainable.
- Super Admin users can manage tenants and licenses even when a tenant is disabled.
- Tenant users are blocked from operational routes when the tenant is inactive, disabled, suspended, or past subscription expiry plus grace period.
- Tenants inside grace period remain operational but see an admin warning banner.

## Tenant-Aware Setup Data

- License, branding, BIR info, invoice footer, and onboarding completion are stored per tenant.
- BIR registration notes are stored in tenant settings.
- Setup update screens reject cross-tenant update attempts for non-Super Admin users.

## Verification Checklist

- `php artisan migrate`
- `php artisan test`
- `php artisan route:list`
- PHP syntax check over changed PHP files
- Manual review:
  - onboarding flow
  - license warnings
  - disabled tenant behavior
  - invoice preview
  - compliance checklist wording

## Remaining TODOs

- Add richer onboarding progress rules once Phase 8 documentation and sample artifacts are finalized.
- Add optional public storage symlink guidance for tenant logo uploads in deployment docs.
- Expand invoice preview to support selectable branch/terminal/template options.
