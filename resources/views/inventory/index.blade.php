@extends('layouts.app', ['heading' => 'Inventory'])

@section('content')
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Product</th><th>Tenant</th><th>Branch</th><th>On Hand</th><th>Reorder Point</th><th>Alert</th><th></th></tr></thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr>
                    <td>{{ $stock->product->name }}<br><span class="text-muted">{{ $stock->product->sku }}</span></td>
                    <td>{{ $stock->tenant->business_name }}</td>
                    <td>{{ $stock->branch->branch_name }}</td>
                    <td>{{ number_format((float) $stock->quantity_on_hand, 3) }}</td>
                    <td>{{ number_format((float) $stock->reorder_point, 3) }}</td>
                    <td>
                        @if ($stock->isLowStock())
                            <span class="badge bg-danger">Low stock</span>
                        @else
                            <span class="badge bg-success">OK</span>
                        @endif
                    </td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.edit', $stock) }}">Edit Alert</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No stock records yet. Record a stock movement to create one.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $stocks->links() }}
@endsection
