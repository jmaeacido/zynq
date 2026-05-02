@extends('layouts.app', ['heading' => 'Stock Movements'])

@section('content')
<div class="mb-3"><a class="btn btn-primary" href="{{ route('stock-movements.create') }}">Record Movement</a></div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Date</th><th>Product</th><th>Tenant / Branch</th><th>Type</th><th>Delta</th><th>Before</th><th>After</th><th>Reason</th></tr></thead>
            <tbody>
            @forelse ($movements as $movement)
                <tr>
                    <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $movement->product->name }}<br><span class="text-muted">{{ $movement->product->sku }}</span></td>
                    <td>{{ $movement->tenant->business_name }}<br><span class="text-muted">{{ $movement->branch->branch_name }}</span></td>
                    <td><span class="badge bg-info">{{ str_replace('_', ' ', $movement->movement_type) }}</span></td>
                    <td>{{ number_format((float) $movement->quantity_delta, 3) }}</td>
                    <td>{{ number_format((float) $movement->quantity_before, 3) }}</td>
                    <td>{{ number_format((float) $movement->quantity_after, 3) }}</td>
                    <td>{{ $movement->reason }}<br><span class="text-muted">{{ $movement->reference_number }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">No stock movements yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $movements->links() }}
@endsection
