# Offline Sync Implementation

ZYNQ now includes a careful post-MVP offline sync foundation for browser-based POS terminals. This feature keeps the current online checkout flow intact and does not claim automatic BIR approval.

## Implemented Foundation

- POS snapshots can be downloaded from `GET /sync/offline-snapshot`.
- Browser POS stores snapshots and queued offline sales in IndexedDB, not `localStorage`.
- Offline sales are queued with `idempotency_key`, `offline_reference`, tenant/branch/terminal/cashier IDs, open cash session ID, `created_offline_at`, and `payload_hash`.
- `POST /sync/offline-sales` validates and recreates sales through the existing `SaleService`.
- `GET /sync/status` and `/sync/conflicts` show server-side sync records and conflicts.
- Sync success creates the official sale, sale items, payments, tax summary, stock movement, financial ledger entry, and audit log through the existing services.

## Invoice Number Rule

ZYNQ uses the fallback safety strategy for this foundation:

- Offline POS receipts use temporary references such as `OFF-TERM-...`.
- Offline receipts must be clearly treated as pending sync and not final BIR invoices.
- The server assigns the official invoice number only after successful sync through the existing `InvoiceNumberService`.
- Duplicate idempotency keys return the existing sync result and do not create another sale.

Reserved invoice ranges remain a future enhancement because the current invoice sequence service is intentionally online and transaction-locked.

## Conflict Rules

The server does not silently modify completed offline sale payloads. It marks the sync record as `conflict` when it detects:

- product inactive or missing
- product price changed
- product tax type changed
- VAT setting changed
- insufficient server stock
- terminal no longer compliant
- cached cash session closed or missing
- tenant, branch, terminal, cashier, or payload hash mismatch

Manager/admin review is required for conflicts. Server inventory and tax settings remain the source of truth.

## Cash Session Rule

Offline sales require a cached open cash session. If no cached session exists, the browser blocks offline sale creation. If the session is closed on the server before sync, the server marks the sale as conflict.

## Security Notes

- Passwords are never stored offline.
- The snapshot contains only POS-operational data.
- Sync endpoints are authenticated and permission-protected.
- Payload hashes are verified on the server.
- Audit logs are written for snapshot download, sync success, conflict, retry, and failure.

## Remaining TODOs

- Manager/admin conflict resolution actions.
- Server-reserved offline invoice number ranges.
- Rich offline invoice print template.
- Broader offline product search and cashier workflow hardening.
- Browser automation for the offline flow.
- CPA/BIR/RDO review before production offline selling is enabled.
