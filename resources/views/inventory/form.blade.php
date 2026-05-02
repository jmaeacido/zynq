@extends('layouts.app', ['heading' => 'Inventory Alert'])

@section('content')
<form method="post" action="{{ route('inventory.update', $stock) }}" class="card">
    @csrf
    @method('put')
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Product</dt><dd class="col-sm-9">{{ $stock->product->name }} ({{ $stock->product->sku }})</dd>
            <dt class="col-sm-3">Tenant</dt><dd class="col-sm-9">{{ $stock->tenant->business_name }}</dd>
            <dt class="col-sm-3">Branch</dt><dd class="col-sm-9">{{ $stock->branch->branch_name }}</dd>
            <dt class="col-sm-3">On hand</dt><dd class="col-sm-9">{{ number_format((float) $stock->quantity_on_hand, 3) }}</dd>
        </dl>
        <div class="mb-3">
            <label class="form-label">Reorder point</label>
            <input name="reorder_point" type="number" step="0.001" min="0" class="form-control" value="{{ old('reorder_point', $stock->reorder_point) }}" required>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Save Alert</button></div>
</form>
@endsection
