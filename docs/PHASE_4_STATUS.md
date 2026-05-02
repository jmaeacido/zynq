# ZYNQ Phase 4 Status

Phase 4 adds formal tax and discount calculation while preserving Phase 3 sale behavior when no discounts are submitted.

## Completed

- Dedicated Tax Engine service.
- Configurable VAT rate through tenant settings, defaulting to 12%.
- VAT and NON_VAT tenant modes.
- Product tax type handling:
  - VATABLE
  - VAT_EXEMPT
  - ZERO_RATED
  - NON_VAT
- Persisted transaction tax summaries in `sale_taxes`.
- Persisted sale discounts in `sale_discounts`.
- Dedicated Discount Service with:
  - regular discount
  - promo discount
  - manual discount with manager/admin approval placeholder
  - senior citizen placeholder
  - PWD placeholder
  - solo parent placeholder
- Sale creation now uses Tax Engine and Discount Service inside the existing database transaction.
- Invoice templates and sale details show tax and discount breakdowns.
- VAT Sales Report.
- Non-VAT Sales Report.
- Discount Report.
- Senior/PWD/Solo Parent placeholder report route/view.
- POS checkout parse error fixed by preparing product JSON in the controller and rendering with `Js::from`.

## Phase Boundary

- Cash sessions, X-reading, and Z-reading remain Phase 5.
- Void/refund/audit ledger remain Phase 6.
- Senior/PWD/Solo Parent statutory formulas are placeholders pending CPA/BIR/RDO confirmation.

## Remaining TODOs

- Phase 5: cash sessions and reading validation.
- Phase 6: void/refund, audit trail persistence, append-only financial ledger, hashes/checksums.
- Add export formats for Phase 4 reports in the reporting/export phase.
