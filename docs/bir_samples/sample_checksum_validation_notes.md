# Sample Checksum and Validation Notes

ZYNQ stores transaction hashes for completed sales, voids, refunds, and ledger entries where implemented.

## Sample Validation Flow

1. Select the transaction or report period.
2. Recompute totals from immutable source rows.
3. Compare report totals against sale line items, tax summaries, payments, and cash session readings.
4. For Z-reading, confirm expected cash equals opening cash plus cash payments under the session.
5. Compare stored hashes with recomputed hashes when a hash helper is available.
6. Record report generation in audit logs.

## Sample Checksum

Input:

```text
invoice_number=SI-MAIN-POS01-000001|total=250.00|created_at=2026-05-02T10:30:00+08:00
```

Example SHA-256:

```text
99f7d8df1f6d8f3cae5f63f8f06cc4e1f7849ad9a3f4d12dd0a16fcf7fba0001
```

This file is a sample artifact only. CPA/BIR/RDO confirmation is required for accepted validation procedures.
