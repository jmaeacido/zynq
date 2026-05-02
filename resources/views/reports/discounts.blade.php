@extends('layouts.app', ['heading' => 'Discount Report'])

@section('content')
<div class="mb-3">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Discount Type</th><th>Count</th><th>Total Amount</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ ucwords(str_replace('_', ' ', $row->discount_type)) }}</td><td>{{ $row->count }}</td><td class="text-end">{{ number_format((float) $row->total_amount, 2) }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">No discounts for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
