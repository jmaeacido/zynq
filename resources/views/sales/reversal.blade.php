@extends('layouts.app', ['heading' => 'Void / Refund Sale'])

@section('content')
<form method="post" action="{{ route('sales.reversals.store', $sale) }}" class="card">
    @csrf
    <div class="card-body">
        <p><strong>{{ $sale->invoice_number }}</strong> - {{ number_format((float) $sale->total_amount, 2) }}</p>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Action</label>
                <select name="type" class="form-select" required>
                    <option value="void">Void full sale</option>
                    <option value="refund">Refund full sale</option>
                    <option value="partial_refund">Partial refund</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Return stock</label>
                <select name="return_to_stock" class="form-select">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" required></textarea>
            </div>
        </div>
        <table class="table table-striped">
            <thead><tr><th>Item</th><th>Sold Qty</th><th>Refund Qty</th></tr></thead>
            <tbody>
            @foreach ($sale->items as $index => $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>{{ number_format((float) $item->quantity, 3) }}</td>
                    <td>
                        <input type="hidden" name="items[{{ $index }}][sale_item_id]" value="{{ $item->id }}">
                        <input name="items[{{ $index }}][quantity]" type="number" step="0.001" min="0" max="{{ $item->quantity }}" class="form-control" value="{{ $item->quantity }}">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer"><button class="btn btn-danger">Record Reversal</button></div>
</form>
@endsection
