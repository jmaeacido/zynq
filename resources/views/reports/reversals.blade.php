@extends('layouts.app', ['heading' => $heading])

@section('content')
<div class="mb-3">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Date</th><th>Invoice</th><th>Type</th><th>Amount</th><th>Approver</th><th>Reason</th><th>Hash</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->created_at->format('Y-m-d H:i') }}</td><td>{{ $row->original_invoice_number }}</td><td>{{ $row->type }}</td><td>{{ number_format((float) $row->amount, 2) }}</td><td>{{ $row->approver->name }}</td><td>{{ $row->reason }}</td><td><code>{{ substr($row->transaction_hash, 0, 16) }}</code></td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No records.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $rows->links() }}
@endsection
