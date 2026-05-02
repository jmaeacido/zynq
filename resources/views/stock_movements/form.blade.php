@extends('layouts.app', ['heading' => 'Record Stock Movement'])

@section('content')
<form method="post" action="{{ route('stock-movements.store') }}" class="card">
    @csrf
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Tenant</label>
                <select name="tenant_id" class="form-select" required>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((int) old('tenant_id') === $tenant->id)>{{ $tenant->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select" required>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((int) old('branch_id') === $branch->id)>{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select" required>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((int) old('product_id') === $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Movement type</label>
                <select name="movement_type" class="form-select" required>
                    <option value="stock_in" @selected(old('movement_type') === 'stock_in')>Stock-in</option>
                    <option value="stock_out" @selected(old('movement_type') === 'stock_out')>Stock-out</option>
                    <option value="adjustment" @selected(old('movement_type') === 'adjustment')>Adjustment</option>
                </select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">Quantity for stock-in/out</label><input name="quantity" type="number" step="0.001" min="0" class="form-control" value="{{ old('quantity') }}"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Target quantity for adjustment</label><input name="target_quantity" type="number" step="0.001" min="0" class="form-control" value="{{ old('target_quantity') }}"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Reference number</label><input name="reference_number" class="form-control" value="{{ old('reference_number') }}"></div>
            <div class="col-md-8 mb-3"><label class="form-label">Reason</label><input name="reason" class="form-control" value="{{ old('reason') }}" required></div>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Record Movement</button></div>
</form>
@endsection
