<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; width: 280px; margin: 0 auto; }
        .center { text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2px 0; }
        .right { text-align: right; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
<button onclick="window.print()">Print</button>
<div class="center">
    <strong>{{ $sale->tenant->business_name }}</strong><br>
    {{ $sale->tenant->trade_name }}<br>
    {{ $sale->tenant->registered_address }}<br>
    TIN: {{ $sale->tenant->tin }}<br>
    {{ $sale->invoice_title }}
</div>
<p>
Invoice: {{ $sale->invoice_number }}<br>
Date: {{ $sale->created_at->format('Y-m-d H:i:s') }}<br>
Branch: {{ $sale->branch->branch_code }}<br>
Terminal: {{ $sale->terminal->terminal_code }}<br>
MIN: {{ $sale->terminal->machine_identification_number }}<br>
PTU: {{ $sale->terminal->permit_to_use_number }}<br>
Accreditation: {{ $sale->terminal->accreditation_number }}<br>
Software: {{ $sale->terminal->software_version }}<br>
Cashier: {{ $sale->cashier->name }}
</p>
<table>
@foreach ($sale->items as $item)
    <tr><td colspan="3">{{ $item->name }}</td></tr>
    <tr><td>{{ number_format((float) $item->quantity, 3) }} x {{ number_format((float) $item->unit_price, 2) }}</td><td>{{ $item->tax_type }}</td><td class="right">{{ number_format((float) $item->line_total, 2) }}</td></tr>
@endforeach
</table>
<hr>
<table>
    <tr><th>Gross Sales</th><td class="right">{{ number_format((float) $sale->taxSummary?->gross_sales, 2) }}</td></tr>
    <tr><th>Discounts</th><td class="right">{{ number_format((float) $sale->taxSummary?->discounts, 2) }}</td></tr>
    <tr><th>VATable Sales</th><td class="right">{{ number_format((float) $sale->taxSummary?->vatable_sales, 2) }}</td></tr>
    <tr><th>VAT Amount</th><td class="right">{{ number_format((float) $sale->taxSummary?->vat_amount, 2) }}</td></tr>
    <tr><th>VAT-Exempt</th><td class="right">{{ number_format((float) $sale->taxSummary?->vat_exempt_sales, 2) }}</td></tr>
    <tr><th>Zero-Rated</th><td class="right">{{ number_format((float) $sale->taxSummary?->zero_rated_sales, 2) }}</td></tr>
    <tr><th>Non-VAT</th><td class="right">{{ number_format((float) $sale->taxSummary?->non_vat_sales, 2) }}</td></tr>
    <tr><th>Total</th><td class="right">{{ number_format((float) $sale->total_amount, 2) }}</td></tr>
    <tr><th>Paid</th><td class="right">{{ number_format((float) $sale->amount_paid, 2) }}</td></tr>
    <tr><th>Change</th><td class="right">{{ number_format((float) $sale->change_due, 2) }}</td></tr>
</table>
<p>Payment: {{ $sale->payments->pluck('payment_method')->map(fn($m) => str_replace('_', ' ', $m))->implode(', ') }}</p>
<p class="center">{{ $sale->tenant->invoice_footer }}</p>
</body>
</html>
