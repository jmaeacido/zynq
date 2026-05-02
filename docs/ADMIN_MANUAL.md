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
