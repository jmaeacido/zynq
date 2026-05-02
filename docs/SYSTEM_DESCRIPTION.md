# System Description

ZYNQ is a Laravel-based Philippine POS system designed to be maintainable, secure, tenant-aware, and BIR-ready for client-specific review.

## Technology

- Laravel latest stable in this workspace.
- PHP 8.2+ compatible target.
- MySQL or MariaDB target database.
- Blade, Bootstrap, and AdminLTE style UI.
- Spatie role/permission package.
- Laravel migrations, form requests, services, middleware, and tests.

## Implemented Domains

- Tenancy and licensing.
- Branches and terminals.
- Users, roles, and permissions.
- Products and categories.
- Inventory and append-only stock movements.
- POS checkout and completed sales.
- Invoice numbering and printable invoice views.
- Tax summaries and discounts.
- Cash sessions and X/Z readings.
- Void/refund workflows.
- Append-only financial ledger.
- Audit logs.
- Reports and CSV exports for selected reports.
- Compliance checklist and invoice preview.

## Core Controls

- Business logic is placed in services instead of controllers.
- Financial and stock operations use database transactions.
- Sales, stock movements, reversals, and ledger entries are append-only.
- Tenant isolation is enforced through query scoping, middleware, and tests.
- Disabled or expired tenants past grace period are blocked from tenant operations.

## Compliance Position

ZYNQ provides compliance artifacts and review-ready reports, but it does not claim automatic BIR approval.
