# Admin Manual

## Super Admin

Super Admin users manage tenants, licensing, and global setup access.

Tasks:
- Create and update tenants.
- Manage license status, subscription expiry, grace period, and disabled state.
- Access disabled tenant license records.
- Review reports and audit trails.

## Tenant Admin

Tenant Admin users manage setup for their own tenant.

Tasks:
- Configure branches.
- Configure terminals and compliance fields.
- Configure BIR information.
- Configure invoice footer and branding.
- Use onboarding checklist.
- Manage users where enabled.

## Licensing

License enforcement is simple:
- `trial`, `active`, and in-grace tenants can operate.
- `disabled`, `suspended`, inactive, and expired-past-grace tenants are blocked from tenant operations.
- Super Admin access remains available for administration.

## Tenant Logo Uploads

Logo uploads are stored on the public disk. Production deployments should run:

```bash
php artisan storage:link
```

## Security Operations

- Use strong passwords.
- Assign least-privilege roles.
- Review audit trail reports.
- Back up database and uploaded files.
- Keep `.env` secret and outside version control.

## Compliance Operations

Use the compliance checklist, invoice preview, BIR setup page, and sample artifacts for CPA/BIR/RDO review. Final interpretation must be confirmed externally.
## Offline Sync Administration

Offline sync records are available from **Offline Sync**. Conflicts appear under **Offline Sync / Conflicts** and require manager/admin review.

Common conflicts include inactive products, changed prices, changed tax settings, insufficient server stock, terminal compliance issues, and cash sessions that were closed before sync. Managers can retry sync, cancel an offline sale, mark it reviewed, or submit a reasoned override only for safe conflict types. Unsafe conflicts such as tenant mismatch, terminal mismatch, duplicate idempotency abuse, payload hash mismatch, and missing compliance data cannot be overridden. ZYNQ does not silently rewrite completed offline sale data.

Offline mode uses temporary references until the server creates the official sale and invoice number. This keeps invoice sequencing server-controlled. Optional reserved invoice ranges are disabled by default with `OFFLINE_INVOICE_RANGE_ENABLED=false`; enabling them requires ownership, compliance, cash-session, overlap, expiry, and CPA/BIR/RDO review.
