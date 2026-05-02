# Offline Sync Plan

ZYNQ is SaaS-ready and multi-branch ready. A browser-based offline sync foundation now exists, but it remains a post-MVP feature that requires client-specific review before production use.

## Target Design

- Browser POS client uses IndexedDB for snapshots and queued sale payloads.
- Server remains the source of truth for tenants, licensing, reports, audit logs, and final synchronization state.
- Offline transactions are written to a local sync queue with immutable payloads and hashes.
- Reconnect flow posts queued sales to server APIs.

## Sync-Compatible Rules

- Financial records are append-only.
- Current foundation uses temporary offline references and assigns official invoice numbers only during server sync.
- Tenant, branch, terminal, and user identifiers must be carried in every queued payload.
- Stock movements must remain append-only, with reversal movements instead of edits.
- Audit events should be queued locally and replayed to the server.

## Tables Prepared for Future Sync

- `tenants`
- `branches`
- `terminals`
- `users`
- `products`
- `product_categories`
- `inventory_stocks`
- `stock_movements`
- `cash_sessions`
- `sales`
- `sale_items`
- `sale_payments`
- `sale_discounts`
- `sale_taxes`
- `invoice_sequences`
- `sale_reversals`
- `financial_ledger_entries`
- `audit_logs`

## Implemented

- IndexedDB snapshot and pending sale queue.
- Snapshot endpoint for tenant, branch, terminal, products, tax settings, cash session, and cashier info.
- Authenticated sync endpoint with idempotency.
- Conflict detection, conflict list, conflict detail view, and manager/admin resolution actions.
- Audit logs for offline snapshot and sync outcomes.
- Optional reserved invoice range endpoints, disabled by default.

## Not Yet Implemented

- API token provisioning for dedicated terminal apps.
- Full browser automation around offline receipt printing and final/synced reprint lifecycle.

CPA, BIR, and RDO confirmation is required before enabling offline sales in production.
