@extends('layouts.app', ['heading' => 'Sales'])

@section('content')
<div class="mb-3"><a class="btn btn-primary" href="{{ route('pos.checkout') }}">New Sale</a></div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Invoice</th><th>Date</th><th>Tenant / Branch</th><th>Terminal</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <td>{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $sale->tenant->business_name }}<br><span class="text-muted">{{ $sale->branch->branch_name }}</span></td>
                    <td>{{ $sale->terminal->terminal_name }}</td>
                    <td>{{ number_format((float) $sale->total_amount, 2) }}</td>
                    <td><span class="badge bg-success">{{ $sale->status }}</span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('sales.show', $sale) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No sales yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $sales->links() }}
@endsection
