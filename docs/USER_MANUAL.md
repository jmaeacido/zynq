# User Manual

This manual describes MVP user workflows. Screen labels may change during client customization.

## Login

Users sign in with their assigned email and password. Access depends on role permissions.

## POS Checkout

1. Open a cash session for the current branch and terminal.
2. Go to POS Checkout.
3. Search or scan active products.
4. Add items to the cart and adjust quantities.
5. Apply supported discounts when available.
6. Select payment method: cash, card, e-wallet, or mixed payments.
7. Enter amount tendered for cash payments.
8. Complete the sale.
9. Open the invoice view and print thermal or A4 format.

Sales are blocked if no cash session is open or if the terminal is missing MIN, PTU, serial number, or software version.

## Cash Sessions

- Open session before selling.
- Expected cash is opening cash plus cash payments.
- Close session with actual cash.
- Cash difference is actual cash minus expected cash.
- Z-reading is available after the session is closed.

## Products and Inventory

Inventory staff can manage products, categories, stock records, and stock movements. Stock movements are append-only.

## Reports

Available reports include daily sales, VAT sales, non-VAT sales, discounts, voids, refunds, audit trail, X-reading, Z-reading, cashier reading, and terminal accountability.

Some reports include CSV export buttons.

## Void and Refund

Void and refund actions require manager/admin approval and a reason. Stock return is explicit and controlled.

## Compliance Note

ZYNQ is BIR-ready, not automatically BIR-approved. Client-specific registration and PTU approval remain required.
## Offline POS Sync

When the POS is online, use **Cache Offline Snapshot** before operating in locations with unstable connectivity. The compact POS toolbar shows online/offline state, pending sync count, last cached snapshot, last sync attempt, and stale snapshot warnings. The snapshot stores only POS data needed for selling, such as active products, prices, tax settings, terminal details, cashier details, and the current open cash session.

If the browser goes offline, ZYNQ shows an offline warning and disables unsupported online-only actions with an explanation. Cashiers may complete sales only from cached products and only when a cached open cash session exists. Offline receipts use temporary references and clearly show **PENDING SYNC - NOT FINAL OFFICIAL INVOICE**.

When connectivity returns, use **Sync Now** from POS Checkout. SweetAlert2 shows loading, success, error, and conflict notices. Synced sales receive official invoice numbers and the POS replaces the temporary reference with final invoice details and a **Reprint final invoice** link. Items that show as conflicts require manager/admin review.

Unsupported offline actions include voids/refunds, report exports, tenant/settings changes, and product or inventory management.
