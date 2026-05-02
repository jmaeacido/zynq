<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $sale->invoice_number }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>@media print { .no-print { display: none; } }</style>
</head>
<body class="p-4">
<button class="btn btn-primary no-print mb-3" onclick="window.print()">Print</button>
<h2>{{ $sale->invoice_title }}</h2>
<div class="row mb-3">
    <div class="col-7">
        <strong>{{ $sale->tenant->business_name }}</strong><br>
        {{ $sale->tenant->trade_name }}<br>
        {{ $sale->tenant->registered_address }}<br>
        TIN: {{ $sale->tenant->tin }}
    </div>
    <div class="col-5">
        Invoice: {{ $sale->invoice_number }}<br>
        Date: {{ $sale->created_at->format('Y-m-d H:i:s') }}<br>
        Branch: {{ $sale->branch->branch_code }}<br>
        Terminal: {{ $sale->terminal->terminal_code }}<br>
        MIN: {{ $sale->terminal->machine_identification_number }}<br>
        PTU: {{ $sale->terminal->permit_to_use_number }}<br>
        Accreditation: {{ $sale->terminal->accreditation_number }}<br>
        Software: {{ $sale->terminal->software_version }}
    </div>
</div>
<table class="table table-bordered">
    <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Tax Type</th><th>Unit Price</th><th>Total</th></tr></thead>
    <tbody>
    @foreach ($sale->items as $item)
        <tr><td>{{ $item->name }}</td><td>{{ number_format((float) $item->quantity, 3) }}</td><td>{{ $item->unit }}</td><td>{{ $item->tax_type }}</td><td>{{ number_format((float) $item->unit_price, 2) }}</td><td>{{ number_format((float) $item->line_total, 2) }}</td></tr>
    @endforeach
    </tbody>
</table>
<table class="table w-50 ms-auto">
    <tr><th>Gross Sales</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->gross_sales, 2) }}</td></tr>
    <tr><th>Discounts</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->discounts, 2) }}</td></tr>
    <tr><th>VATable Sales</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->vatable_sales, 2) }}</td></tr>
    <tr><th>VAT Amount</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->vat_amount, 2) }}</td></tr>
    <tr><th>VAT-Exempt Sales</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->vat_exempt_sales, 2) }}</td></tr>
    <tr><th>Zero-Rated Sales</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->zero_rated_sales, 2) }}</td></tr>
    <tr><th>Non-VAT Sales</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->non_vat_sales, 2) }}</td></tr>
    <tr><th>Total Amount Due</th><td class="text-end">{{ number_format((float) $sale->total_amount, 2) }}</td></tr>
    <tr><th>Amount Paid</th><td class="text-end">{{ number_format((float) $sale->amount_paid, 2) }}</td></tr>
    <tr><th>Change</th><td class="text-end">{{ number_format((float) $sale->change_due, 2) }}</td></tr>
</table>
<p>Payment method: {{ $sale->payments->pluck('payment_method')->map(fn($m) => str_replace('_', ' ', $m))->implode(', ') }}</p>
<p>{{ $sale->tenant->invoice_footer }}</p>
</body>
</html>
