# Offline Sync Plan

ZYNQ is SaaS-ready and multi-branch ready, but full offline synchronization is not implemented in the MVP. This plan keeps the current schema compatible with a future offline-capable POS client.

## Target Design

- Local POS client uses SQLite for products, branches, terminals, invoice sequences, cash sessions, and queued transactions.
- Server remains the source of truth for tenants, licensing, reports, audit logs, and final synchronization state.
- Offline transactions are written to a local sync queue with immutable payloads and hashes.
- Reconnect flow posts queued sales, stock movements, and cash session events to server APIs in original order.

## Sync-Compatible Rules

- Financial records are append-only.
- Invoice numbers must be reserved or generated using a branch/terminal-aware range strategy before offline selling is enabled.
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

## Not Yet Implemented

- Local SQLite POS runtime.
- API token provisioning for terminals.
- Conflict resolution UI.
- Invoice range reservation.
- Sync queue processor.
- Server-side idempotency keys.

CPA, BIR, and RDO confirmation is required before enabling offline sales in production.
