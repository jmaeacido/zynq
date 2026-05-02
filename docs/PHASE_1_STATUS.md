# ZYNQ Phase 1 Status

Phase 1 establishes the Laravel 13 foundation, authentication, RBAC, tenants, branches, terminals, settings storage, and terminal compliance readiness checks.

## Completed

- Laravel 13 project scaffold with PHP 8.3+ constraint.
- Spatie Laravel Permission installed and configured.
- Roles seeded: Super Admin, Tenant Admin, Branch Manager, Cashier, Auditor, Inventory Staff.
- Tenant, branch, terminal, and tenant setting tables and domain models.
- Tenant and branch isolation middleware.
- Login/logout flow with hashed passwords and CSRF-protected forms.
- AdminLTE/Bootstrap Blade layout and Phase 1 screens.
- Terminal compliance helper requiring MIN, PTU number, serial number, and software version before a terminal can be considered sale-ready.
- Visible compliance disclaimer on admin pages.

## BIR-Ready Boundary

ZYNQ is built to collect and display compliance artifacts for review. It does not claim automatic BIR approval. Each client must still complete RDO/CPA review, registration, PTU approval, and any required submission steps.

## Phase 1 TODOs For Later Phases

- Audit log persistence is scheduled for Phase 6.
- Invoice numbering and sale blocking will be enforced in Phase 3 when sale creation exists.
- Full license enforcement and onboarding wizard are scheduled for Phase 7.
