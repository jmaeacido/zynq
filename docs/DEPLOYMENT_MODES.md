# Deployment Modes

ZYNQ supports three practical deployment shapes.

## Single-Branch Standalone

Use one Laravel installation, one database, one tenant, one branch, and one or more terminals inside the same location.

Best for:
- Small shops.
- One physical selling location.
- Simple local network deployment.

Notes:
- Backups must be configured locally.
- If tenant logo uploads are enabled, run `php artisan storage:link` so files in `storage/app/public` are reachable from `public/storage`.
- Keep `.env` database, mail, queue, and app key values private.

## Multi-Branch Server

Use one central Laravel installation and database serving multiple branches under one tenant.

Best for:
- Retailers with multiple branches.
- Central reporting.
- Shared product catalog and stock visibility.

Notes:
- Branch and terminal isolation must be enforced by user permissions and tenant/branch middleware.
- Network uptime matters because offline sync is not implemented yet.
- Database backups should be scheduled and tested.

## SaaS-Ready Multi-Tenant

Use one hosted Laravel installation serving multiple client tenants.

Best for:
- Commercial deployment to multiple clients.
- Super Admin license and tenant management.
- Centralized updates.

Notes:
- Every tenant-owned table must remain tenant-aware.
- Disabled tenants are blocked from operational routes while Super Admin access remains available.
- Use HTTPS, managed backups, queue workers, log retention, and monitored database storage.
- Do not reuse client-specific BIR data across tenants.

## Production Checklist

- Run `composer install --no-dev --optimize-autoloader`.
- Run `php artisan key:generate` once per environment.
- Run `php artisan migrate --force`.
- Run `php artisan storage:link` for tenant logo uploads.
- Configure scheduler and queues if jobs are introduced.
- Configure database backups and restore testing.
- Restrict server shell and database access.
