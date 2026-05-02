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
