@extends('layouts.app', ['heading' => 'Offline Sync Conflict'])

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>{{ $record->offline_reference }}</strong>
        <span class="badge bg-{{ $record->status === 'conflict' ? 'danger' : 'secondary' }}">{{ $record->statusLabel() }}</span>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-md-3">Idempotency Key</dt><dd class="col-md-9"><code>{{ $record->idempotency_key }}</code></dd>
            <dt class="col-md-3">Cashier</dt><dd class="col-md-9">{{ $record->cashier?->name }}</dd>
            <dt class="col-md-3">Terminal</dt><dd class="col-md-9">{{ $record->terminal?->terminal_name }}</dd>
            <dt class="col-md-3">Branch</dt><dd class="col-md-9">{{ $record->branch?->branch_name }}</dd>
            <dt class="col-md-3">Cash Session</dt><dd class="col-md-9">{{ $record->cash_session_id }}</dd>
            <dt class="col-md-3">Official Invoice</dt><dd class="col-md-9">{{ $record->sale?->invoice_number ?? 'Pending Sync' }}</dd>
        </dl>
        <h5>Conflicts</h5>
        <ul>
            @foreach (($record->conflicts ?? []) as $conflict)
                <li><code>{{ $conflict['code'] ?? 'conflict' }}</code> {{ $conflict['message'] ?? 'Offline sync conflict' }}</li>
            @endforeach
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Resolution Actions</strong></div>
    <div class="card-body d-flex flex-wrap gap-2">
        <form method="post" action="{{ route('sync.conflicts.retry', $record) }}">@csrf<button class="btn btn-outline-primary">Retry Sync</button></form>
        <form method="post" action="{{ route('sync.conflicts.review', $record) }}">@csrf<input class="form-control mb-2" name="reason" placeholder="Review reason"><button class="btn btn-outline-secondary">Mark Reviewed</button></form>
        <form method="post" action="{{ route('sync.conflicts.cancel', $record) }}">@csrf<input class="form-control mb-2" name="reason" placeholder="Cancel reason"><button class="btn btn-outline-danger">Cancel Offline Sale</button></form>
        <form method="post" action="{{ route('sync.conflicts.override', $record) }}">@csrf<input class="form-control mb-2" name="reason" required placeholder="Manager override reason"><button class="btn btn-warning">Manager Override</button></form>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><strong>Original Payload</strong></div>
            <div class="card-body"><pre class="small mb-0">{{ json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><strong>Server Comparison</strong></div>
            <div class="card-body"><pre class="small mb-0">{{ json_encode([
                'branch' => $record->branch?->only(['id', 'branch_name', 'branch_code']),
                'terminal' => $record->terminal?->only(['id', 'terminal_name', 'terminal_code', 'active']),
                'sale' => $record->sale?->only(['id', 'invoice_number', 'status', 'total_amount']),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
        </div>
    </div>
</div>
@endsection
