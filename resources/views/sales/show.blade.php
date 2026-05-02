@extends('layouts.app', ['heading' => 'Sale Details'])

@section('content')
<div class="mb-3">
    <a class="btn btn-outline-primary" href="{{ route('sales.invoice.thermal', $sale) }}">Thermal Invoice</a>
    <a class="btn btn-outline-primary" href="{{ route('sales.invoice.a4', $sale) }}">A4 Invoice</a>
    @canany(['approve voids', 'approve refunds'])
        <a class="btn btn-outline-danger" href="{{ route('sales.reversals.create', $sale) }}">Void / Refund</a>
    @endcanany
</div>
<div class="card">
    <div class="card-header"><strong>{{ $sale->invoice_number }}</strong></div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Tenant</dt><dd class="col-sm-9">{{ $sale->tenant->business_name }}</dd>
            <dt class="col-sm-3">Branch</dt><dd class="col-sm-9">{{ $sale->branch->branch_name }}</dd>
            <dt class="col-sm-3">Terminal</dt><dd class="col-sm-9">{{ $sale->terminal->terminal_name }}</dd>
            <dt class="col-sm-3">Cashier</dt><dd class="col-sm-9">{{ $sale->cashier->name }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $sale->status }}</dd>
        </dl>
        <table class="table table-striped">
            <thead><tr><th>Item</th><th>Tax Type</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
            @foreach ($sale->items as $item)
                <tr><td>{{ $item->name }}</td><td>{{ $item->tax_type }}</td><td>{{ number_format((float) $item->quantity, 3) }}</td><td>{{ number_format((float) $item->unit_price, 2) }}</td><td>{{ number_format((float) $item->line_total, 2) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        <table class="table table-sm w-50 ms-auto">
            <tr><th>Subtotal</th><td class="text-end">{{ number_format((float) $sale->subtotal, 2) }}</td></tr>
            <tr><th>Discounts</th><td class="text-end">{{ number_format((float) $sale->discount_total, 2) }}</td></tr>
            <tr><th>VATable Sales</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->vatable_sales, 2) }}</td></tr>
            <tr><th>VAT Amount</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->vat_amount, 2) }}</td></tr>
            <tr><th>VAT-Exempt Sales</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->vat_exempt_sales, 2) }}</td></tr>
            <tr><th>Zero-Rated Sales</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->zero_rated_sales, 2) }}</td></tr>
            <tr><th>Non-VAT Sales</th><td class="text-end">{{ number_format((float) $sale->taxSummary?->non_vat_sales, 2) }}</td></tr>
            <tr><th>Total</th><td class="text-end">{{ number_format((float) $sale->total_amount, 2) }}</td></tr>
            <tr><th>Paid</th><td class="text-end">{{ number_format((float) $sale->amount_paid, 2) }}</td></tr>
            <tr><th>Change</th><td class="text-end">{{ number_format((float) $sale->change_due, 2) }}</td></tr>
        </table>
    </div>
</div>
<div class="card mt-3">
    <div class="card-header"><strong>Void / Refund History</strong></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Return Stock</th><th>Reason</th><th>Hash</th></tr></thead>
            <tbody>
            @forelse ($sale->reversals as $reversal)
                <tr>
                    <td>{{ $reversal->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $reversal->type }}</td>
                    <td>{{ number_format((float) $reversal->amount, 2) }}</td>
                    <td>{{ $reversal->return_to_stock ? 'Yes' : 'No' }}</td>
                    <td>{{ $reversal->reason }}</td>
                    <td><code>{{ substr($reversal->transaction_hash, 0, 16) }}</code></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No voids or refunds.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
