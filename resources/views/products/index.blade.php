@extends('layouts.app', ['heading' => 'Products'])

@section('content')
<div class="mb-3"><a class="btn btn-primary" href="{{ route('products.create') }}">New Product</a></div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Product</th><th>Tenant</th><th>Category</th><th>Price</th><th>Tax Type</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->name }}<br><span class="text-muted">SKU: {{ $product->sku }} @if($product->barcode) | Barcode: {{ $product->barcode }} @endif</span></td>
                    <td>{{ $product->tenant->business_name }}</td>
                    <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
                    <td>{{ number_format((float) $product->selling_price, 2) }}</td>
                    <td><span class="badge bg-info">{{ $product->tax_type }}</span></td>
                    <td><span class="badge bg-{{ $product->active ? 'success' : 'secondary' }}">{{ $product->active ? 'Active' : 'Archived' }}</span></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('products.edit', $product) }}">Edit</a>
                        @if ($product->active)
                            <form method="post" action="{{ route('products.archive', $product) }}" class="d-inline">
                                @csrf
                                @method('patch')
                                <button class="btn btn-sm btn-outline-secondary">Archive</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No products yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $products->links() }}
@endsection
