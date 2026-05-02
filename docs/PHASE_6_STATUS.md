# ZYNQ Phase 6 Status

Phase 6 adds void/refund workflows, append-only financial ledger entries, audit log persistence, transaction hashes, and reversal reports.

## Completed

- Void sale workflow.
- Full refund workflow.
- Partial refund workflow.
- Manager/admin approval enforced through `approve voids` and `approve refunds` permissions.
- Required reason for every void/refund.
- Original invoice reference stored on every reversal.
- Append-only `financial_ledger_entries`.
- Append-only `sale_reversals`.
- `audit_logs` persistence.
- Transaction hash for sale, void, refund, and partial refund ledger entries.
- Report checksum helper via `TransactionIntegrityService::checksum`.
- Tamper verification helper via `TransactionIntegrityService::verify`.
- Void report.
- Refund report.
- Audit trail report.
- Sale details now show void/refund history.
- Optional stock reversal controlled by `return_to_stock`.

## Safety Rule

Voids/refunds are blocked after the sale cash session is closed. This preserves X/Z-reading reproducibility and prevents retroactive changes to closed cashier accountability periods. Later deployment policy may add a manager-supervised post-close adjustment workflow, but Phase 6 keeps closed sessions immutable.

## Phase Boundary

- Reversals create separate records and ledger entries. Completed sale original values are not overwritten.
- No hard deletion of completed financial records, reversals, or ledger entries.

## Remaining TODOs

- Expand audit coverage beyond void/refund to all sensitive modules.
- Add export files for void/refund/audit reports.
- Add post-close adjustment policy only if approved by CPA/BIR/RDO review.
