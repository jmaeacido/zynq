# Database Dictionary

This dictionary summarizes implemented MVP tables.

| Table | Purpose | Tenant-Aware |
| --- | --- | --- |
| `users` | Authenticated system users with tenant and branch assignment | Yes |
| `tenants` | Client profile, BIR info, license status, branding, invoice footer | Primary tenant table |
| `branches` | Tenant branch records and registered addresses | Yes |
| `terminals` | POS machine compliance and setup fields | Yes |
| `tenant_settings` | Per-tenant configurable settings | Yes |
| `product_categories` | Product category records | Yes |
| `products` | Sellable items with SKU, barcode, prices, and tax type | Yes |
| `inventory_stocks` | Branch-level stock on hand and reorder point | Yes |
| `stock_movements` | Append-only inventory movements | Yes |
| `invoice_sequences` | Branch/terminal/document invoice numbering | Yes |
| `cash_sessions` | Cashier session open/close records | Yes |
| `sales` | Completed, voided, refunded, and partially refunded sales | Yes |
| `sale_items` | Sale line items and tax type snapshot | Yes |
| `sale_payments` | Payment method and tendered/change details | Yes |
| `sale_discounts` | Discount records with type, reason, reference, approver | Yes |
| `sale_taxes` | Transaction tax summaries | Yes |
| `sale_reversals` | Void/refund/partial refund records | Yes |
| `financial_ledger_entries` | Append-only financial ledger entries | Yes |
| `audit_logs` | Audit trail for sensitive actions | Yes |
| Spatie permission tables | Roles, permissions, and assignments | Global/package-managed |

## Append-Only Tables

- `stock_movements`
- `financial_ledger_entries`
- Completed financial records should be corrected through reversals or adjustments, not silent edits.

## Important Tenant Fields

- `tenants.license_key`
- `tenants.license_status`
- `tenants.subscription_expires_at`
- `tenants.grace_period_days`
- `tenants.logo_path`
- `tenants.invoice_footer`
- `tenants.onboarding_completed_at`

## Tax Types

Products support:
- `VATABLE`
- `VAT_EXEMPT`
- `ZERO_RATED`
- `NON_VAT`
