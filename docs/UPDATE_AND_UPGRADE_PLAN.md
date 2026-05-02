# Update and Upgrade Plan

ZYNQ uses Laravel migrations and documentation-driven release notes for updates.

## Current MVP Approach

- Code updates are deployed through source control or packaged release archives.
- Database changes are applied through Laravel migrations.
- Tests are run before production rollout.
- Report and compliance artifacts are versioned in `docs/`.

## Recommended Upgrade Flow

1. Back up the database and uploaded files.
2. Put the system in maintenance mode if required.
3. Deploy the new application code.
4. Run `composer install --no-dev --optimize-autoloader`.
5. Run `php artisan migrate --force`.
6. Clear and rebuild caches with `php artisan optimize:clear` and `php artisan optimize`.
7. Run smoke checks for login, dashboard, POS, reports, license, onboarding, invoice preview, and compliance checklist.
8. Record the deployed version and migration batch.

## Version Tracking

The MVP relies on migration history and phase status documents. A dedicated system version table or release registry remains a post-MVP enhancement.

## Rollback Guidance

- Prefer forward-fix migrations where financial data is involved.
- Do not roll back migrations that would remove financial, audit, invoice, or ledger records without a reviewed recovery plan.
- CPA/BIR/RDO review may be needed when report or invoice behavior changes.
