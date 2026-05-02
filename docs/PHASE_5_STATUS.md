# ZYNQ Phase 5 Status

Phase 5 adds cashier cash sessions and reading reports.

## Completed

- Cash session table and model.
- Open cashier session with tenant, branch, terminal, cashier, and opening cash.
- Close cashier session with expected cash, actual cash, and cash difference.
- Session status: open/closed.
- Sales are associated to `cash_session_id`.
- Sale creation is blocked unless the cashier has an open session for the selected tenant/branch/terminal.
- Duplicate active sessions for the same tenant/branch/terminal/user are blocked by service validation.
- Closing an already closed session is blocked.
- X-reading report from live session totals.
- Z-reading report for closed sessions with validation that expected cash matches completed sale cash totals.
- Cashier reading report.
- Terminal accountability report.
- Daily sales report integration.
- AdminLTE pages for cash sessions and readings.

## Validation Rules

- Expected cash = opening cash + completed sale cash payments.
- Card and e-wallet payments are reported separately and are not included in expected cash.
- Cash difference = actual cash - expected cash.
- Z-reading is tied to a specific cash session, tenant, branch, and terminal.

## Phase Boundary

- Void/refund remains Phase 6.
- Full audit ledger remains Phase 6.
- Cash session report exports are deferred to the reporting/export phase.

## Remaining TODOs

- Phase 6: void/refund workflow, financial ledger, audit logs, transaction hashes/checksums.
- Add cashier shift handoff workflow if required by deployment policy.
- Add PDF exports and broader export formats beyond the CSV handlers added during finalization.
