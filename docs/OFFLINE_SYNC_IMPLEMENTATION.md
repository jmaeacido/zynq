# Offline Sync Implementation

ZYNQ now includes a careful post-MVP offline sync foundation for browser-based POS terminals. This feature keeps the current online checkout flow intact and does not claim automatic BIR approval.

## Implemented Foundation

- POS snapshots can be downloaded from `GET /sync/offline-snapshot`.
- Browser POS stores snapshots and queued offline sales in IndexedDB, not `localStorage`.
- Offline sales are queued with `idempotency_key`, `offline_reference`, tenant/branch/terminal/cashier IDs, open cash session ID, `created_offline_at`, and `payload_hash`.
- `POST /sync/offline-sales` validates and recreates sales through the existing `SaleService`.
- `GET /sync/status` shows server-side sync records. `GET /sync/conflicts` and `GET /sync/conflicts/{id}` show manager/admin conflict queues and detail.
- Conflict actions are available through `POST /sync/conflicts/{id}/retry`, `/cancel`, `/review`, and `/override`.
- Sync success creates the official sale, sale items, payments, tax summary, stock movement, financial ledger entry, and audit log through the existing services.

## Invoice Number Rule

ZYNQ uses the fallback safety strategy by default:

- Offline POS receipts use temporary references such as `OFF-TERM-...`.
- Offline receipts must display `PENDING SYNC - NOT FINAL OFFICIAL INVOICE`.
- The server assigns the official invoice number only after successful sync through the existing `InvoiceNumberService`.
- Duplicate idempotency keys return the existing sync result and do not create another sale.
- Synced records permanently link `offline_reference` to `sale_id` and the server invoice number.

Reserved invoice ranges are optional and disabled by default through `OFFLINE_INVOICE_RANGE_ENABLED=false`. If enabled, terminals can reserve ranges through `POST /sync/invoice-ranges/reserve` and inspect `GET /sync/invoice-ranges/status`; sync rejects reused/out-of-range submitted offline invoice numbers. CPA/BIR/RDO review is still required before enabling this mode.

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

Manager/admin review is required for conflicts. Retry, cancel, review, and safe override attempts are audited. Unsafe conflicts cannot be overridden: tenant mismatch, terminal mismatch, duplicate idempotency abuse, payload hash mismatch, terminal compliance failure, and missing reserved range compliance. Server inventory and tax settings remain the source of truth.

## Cash Session Rule

Offline sales require a cached open cash session. If no cached session exists, the browser blocks offline sale creation. If the session is closed on the server before sync, the server marks the sale as conflict.

## Security Notes

- Passwords are never stored offline.
- The snapshot contains only POS-operational data.
- Sync endpoints are authenticated and permission-protected.
- Payload hashes are verified on the server.
- Audit logs are written for snapshot download, sync success, conflict, retry, cancellation, review, override attempt, range reservation, and failure.

## Remaining TODOs

- Rich offline invoice print template.
- Browser automation for the offline flow.
- CPA/BIR/RDO review before production offline selling is enabled.
