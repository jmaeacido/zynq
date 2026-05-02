# Testing Checklist

## Automated Tests

Run:

```bash
php artisan test
```

Covered areas:
- Tenant isolation.
- Role restrictions.
- Product and inventory behavior.
- Stock movements and low stock detection.
- Sale creation and stock deduction.
- Invoice numbering.
- Terminal compliance blocking.
- Tax and discount calculations.
- Cash session open/close and X/Z totals.
- Void/refund approval and stock reversal.
- Ledger immutability.
- Audit log creation.
- Licensing and disabled tenant behavior.
- BIR info tenant isolation.
- Invoice preview and compliance disclaimer.

## Manual Smoke Tests

- Login as Super Admin.
- Open dashboard.
- Open tenant list and license page.
- Open onboarding wizard.
- Open BIR info setup.
- Open invoice settings.
- Open invoice preview and switch template/branch/terminal.
- Open compliance checklist.
- Login as cashier.
- Open cash session.
- Create POS sale.
- View sale and invoice.
- Close cash session.
- View X/Z readings.
- Export CSV from reports where available.

## Final Review

Confirm no hard-coded client-specific BIR data exists. Confirm the visible wording says “BIR-ready, not automatically BIR-approved.” Confirm CPA/BIR/RDO review is marked as required where legal or accounting interpretation is involved.
