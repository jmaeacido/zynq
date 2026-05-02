# ZYNQ Phase 3 Status

Phase 3 adds POS checkout, completed sales, payments, invoice numbering, invoice viewing/printing, and sale-time stock deduction.

## Completed

- POS checkout screen with branch, terminal, barcode/search input, cart table, quantity controls, payment panel, total, tendered amount, and change display.
- Product lookup endpoint for active tenant-scoped products.
- Sale creation service with database transaction.
- `sales`, `sale_items`, `sale_payments`, and `invoice_sequences` tables.
- Sale status stored as `completed`.
- Cash, card, e-wallet, and mixed payment rows.
- Amount paid and change due computation.
- Branch/terminal-aware invoice sequence with unique invoice numbers per tenant, branch, terminal, and document type.
- Sale-time terminal compliance blocking using MIN, PTU, serial number, and software version checks.
- Sale-time stock deduction through the existing inventory service.
- Sale stock deduction creates append-only `stock_movements` rows.
- Sale details page.
- Thermal and A4 printable invoice templates.
- Tenant/branch/terminal/product isolation checks in sale creation.
- Tests for sale creation, stock deduction, invoice numbering, terminal compliance blocking, cross-tenant product blocking, and invoice rendering.

## Phase Boundary

- Full VAT/non-VAT computation is intentionally deferred to Phase 4.
- Void/refund workflows are intentionally deferred to Phase 6.
- Full audit persistence is intentionally deferred to Phase 6.

## Remaining TODOs

- Phase 4: formal tax engine, discount service, VAT/non-VAT summaries.
- Phase 5: cash sessions, X-reading, Z-reading.
- Phase 6: void/refund, ledger, audit logs, hashes/checksums.
- Improve checkout product search into a richer async search/select widget after frontend tooling is available.
