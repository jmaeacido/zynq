@extends('layouts.app', ['heading' => $product->exists ? 'Edit Product' : 'New Product'])

@section('content')
<form method="post" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="card">
    @csrf
    @if ($product->exists) @method('put') @endif
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant</label>
                <select name="tenant_id" class="form-select" required>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((int) old('tenant_id', $product->tenant_id) === $tenant->id)>{{ $tenant->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">Uncategorized</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label">SKU</label><input name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Barcode</label><input name="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Unit</label><input name="unit" class="form-control" value="{{ old('unit', $product->unit ?: 'pcs') }}" required></div>
            <div class="col-md-12 mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $product->name) }}" required></div>
            <div class="col-md-12 mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control">{{ old('description', $product->description) }}</textarea></div>
            <div class="col-md-4 mb-3"><label class="form-label">Selling price</label><input name="selling_price" type="number" step="0.01" min="0" class="form-control" value="{{ old('selling_price', $product->selling_price ?? 0) }}" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Cost price</label><input name="cost_price" type="number" step="0.01" min="0" class="form-control" value="{{ old('cost_price', $product->cost_price ?? 0) }}" required></div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Tax type</label>
                <select name="tax_type" class="form-select" required>
                    @foreach (['VATABLE', 'VAT_EXEMPT', 'ZERO_RATED', 'NON_VAT'] as $taxType)
                        <option value="{{ $taxType }}" @selected(old('tax_type', $product->tax_type ?: 'VATABLE') === $taxType)>{{ $taxType }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12 mb-3"><div class="form-check"><input type="hidden" name="active" value="0"><input id="active" name="active" value="1" type="checkbox" class="form-check-input" @checked(old('active', $product->active ?? true))><label class="form-check-label" for="active">Active product</label></div></div>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Save Product</button></div>
</form>
@endsection
