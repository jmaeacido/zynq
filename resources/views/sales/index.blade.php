@extends('layouts.app', ['heading' => 'Sales'])

@section('content')
<div class="mb-2"><a class="btn btn-primary btn-sm" href="{{ route('pos.checkout') }}"><i class="fas fa-plus me-1"></i>New Sale</a></div>
<div class="card">
    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-striped mb-0">
            <thead><tr><th>Invoice</th><th>Date</th><th>Tenant / Branch</th><th>Terminal</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($sales as $sale)
                <tr>
                    <td><strong>{{ $sale->invoice_number }}</strong></td>
                    <td title="{{ $sale->created_at->toDayDateTimeString() }}">{{ $sale->created_at->diffForHumans() }}</td>
                    <td>{{ $sale->tenant->business_name }}<br><span class="text-muted">{{ $sale->branch->branch_name }}</span></td>
                    <td>{{ $sale->terminal->terminal_name }}</td>
                    <td>{{ number_format((float) $sale->total_amount, 2) }}</td>
                    <td><span class="badge bg-success">{{ $sale->status }}</span></td>
                    <td class="text-end sticky-actions"><a class="btn btn-sm btn-outline-primary" href="{{ route('sales.show', $sale) }}"><i class="fas fa-eye me-1"></i>View</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state">No sales yet.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $sales->links() }}
@endsection
