# BIR Compliance Matrix

ZYNQ is BIR-ready, not automatically BIR-approved. Final requirements must be confirmed with the client CPA, BIR RDO, and current BIR rules.

| Area | Implemented MVP Support | Review Required |
| --- | --- | --- |
| Tenant taxpayer profile | Business name, trade name, registered address, TIN, taxpayer type, RDO code | Verify exact registration data |
| Branch registration | Branch code, address, BIR registered address, status | Confirm branch registration requirements |
| Terminal registration | MIN, PTU, serial number, accreditation number, software version | Confirm PTU and accreditation details |
| Invoice wording | Uses Sales/Cash/Charge Invoice wording, not Official Receipt by default | Confirm wording for client taxpayer type |
| Invoice numbering | Sequential tenant/branch/terminal/document type sequences with DB lock | Confirm sequence format and reset policy |
| Tax summaries | VAT, VAT-exempt, zero-rated, non-VAT, gross, discounts, net, total due | CPA validation required |
| Cash sessions | Opening cash, expected cash, actual cash, difference, X/Z readings | Confirm local reporting expectations |
| Void/refund | Approval, reason, original invoice reference, reversal records | Confirm required reports and approval policy |
| Audit trail | Sales, reversals, setup, licensing, invoice preview, compliance views, and report exports where implemented | Confirm retention period and whether login/logout auditing is required |
| Data integrity | Transaction hashes, ledger hashes, checksum helper notes | Confirm accepted validation method |
| Reports | Daily sales, VAT/non-VAT, discount, void, refund, audit, readings | Confirm BIR-required report set |
| Documentation | System, user/admin manual, DB dictionary, test checklist, samples | Submit as requested by RDO/CPA |

## Explicit Disclaimer

This system is BIR-ready, not automatically BIR-approved. Client-specific registration, PTU processing, and BIR/RDO/CPA review remain required.
## Offline Sync Notes

Offline sync is BIR-ready as a technical foundation, not automatically BIR-approved. Offline receipts use temporary references by default and must be marked `PENDING SYNC - NOT FINAL OFFICIAL INVOICE`. Official invoice numbers are assigned only by the server after successful sync through the existing invoice sequence service.

Reserved offline invoice ranges are optional and disabled by default with `OFFLINE_INVOICE_RANGE_ENABLED=false`. If enabled, range ownership, terminal compliance, open cash session, overlap, expiry, and out-of-range checks apply. CPA/BIR/RDO review is still required before production use.

Final CPA, BIR, and RDO review is still required before enabling offline sales in production.
