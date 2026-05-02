# ZYNQ Phase 2 Status

Phase 2 adds product catalog and inventory foundations while keeping POS checkout and sales out of scope.

## Completed

- Tenant-aware product categories.
- Tenant-aware products with SKU, barcode, unit, prices, active/archive status, and tax type support:
  - VATABLE
  - VAT_EXEMPT
  - ZERO_RATED
  - NON_VAT
- Branch-level inventory stock records with quantity on hand and reorder point.
- Append-only stock movements for stock-in, stock-out, and adjustment.
- Inventory service methods wrap stock changes in database transactions.
- Stock-out prevents negative stock.
- Product archive flow avoids hard deletion.
- Low stock detection using `quantity_on_hand <= reorder_point`.
- AdminLTE/Bootstrap pages for categories, products, inventory, and stock movements.
- Tenant and branch validation for all product and inventory writes.
- Tests for cross-tenant blocking, stock movement creation, stock adjustment, append-only movement protection, and low stock detection.

## Audit-Ready Notes

Stock movements are the Phase 2 audit-ready inventory trail. They store tenant, branch, product, user, movement type, before quantity, delta, after quantity, reason, reference number, and metadata. Full audit log persistence remains scheduled for Phase 6.

## Boundaries

- POS checkout, sale stock deduction, refunds, and invoice numbering are not implemented in Phase 2.
- Stock movements cannot be updated or deleted through the model.
- Products are archived by setting inactive status rather than hard-deleted.

## Remaining TODOs

- Phase 3 will consume inventory services for sale stock deduction.
- Phase 6 will write formal audit log rows for product and stock changes.
- Future import/export can add CSV/Excel workflows for product and opening inventory loading.
