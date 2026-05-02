@extends('layouts.app', ['heading' => 'Offline Sync Conflict'])

@section('content')
@php
    $unsafeCodes = ['tenant_mismatch', 'terminal_mismatch', 'duplicate_idempotency_abuse', 'payload_hash_mismatch', 'terminal_not_compliant', 'offline_invoice_range_invalid'];
    $codes = collect($record->conflicts ?? [])->pluck('code')->filter();
    $unsafe = $codes->intersect($unsafeCodes)->isNotEmpty();
@endphp
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>{{ $record->offline_reference }}</strong>
        <div>
            <span class="badge bg-{{ $record->status === 'conflict' ? 'danger' : 'secondary' }}">{{ $record->statusLabel() }}</span>
            <span class="badge bg-{{ $unsafe ? 'dark' : 'info' }}">{{ $unsafe ? 'Unsafe' : 'Reviewable' }}</span>
        </div>
    </div>
    <div class="card-body">
        <dl class="row mb-2">
            <dt class="col-md-3">Offline Reference</dt><dd class="col-md-9">{{ $record->offline_reference }}</dd>
            <dt class="col-md-3">Cashier</dt><dd class="col-md-9">{{ $record->cashier?->name }}</dd>
            <dt class="col-md-3">Branch</dt><dd class="col-md-9">{{ $record->branch?->branch_name }}</dd>
            <dt class="col-md-3">Terminal</dt><dd class="col-md-9">{{ $record->terminal?->terminal_name }} <span class="text-muted">({{ $record->terminal?->terminal_code }})</span></dd>
            <dt class="col-md-3">Cash Session</dt><dd class="col-md-9">#{{ $record->cash_session_id }} <span class="text-muted">{{ $record->cashSession?->status }}</span></dd>
            <dt class="col-md-3">Created Offline</dt><dd class="col-md-9">{{ $record->created_offline_at?->toDayDateTimeString() }}</dd>
            <dt class="col-md-3">Resolution Status</dt><dd class="col-md-9">{{ $record->statusLabel() }}{{ $record->resolution_reason ? ' - '.$record->resolution_reason : '' }}</dd>
            <dt class="col-md-3">Official Sale/Invoice</dt>
            <dd class="col-md-9">
                @if ($record->sale)
                    <a href="{{ route('sales.show', $record->sale) }}">Sale #{{ $record->sale->id }}</a> /
                    <a href="{{ route('sales.invoice.thermal', $record->sale) }}">{{ $record->sale->invoice_number }}</a>
                @else
                    <span class="badge bg-warning text-dark">Pending Sync</span>
                @endif
            </dd>
            <dt class="col-md-3">Idempotency Key</dt><dd class="col-md-9"><code>{{ $record->idempotency_key }}</code></dd>
        </dl>
        <h6 class="mb-2">Conflict Type / Message</h6>
        <div class="d-flex flex-wrap gap-1 mb-2">
            @foreach ($codes as $code)
                <span class="badge bg-{{ in_array($code, $unsafeCodes, true) ? 'dark' : 'secondary' }}">{{ str_replace('_', ' ', $code) }}</span>
            @endforeach
        </div>
        <ul class="small mb-0 ps-3">
            @foreach (($record->conflicts ?? []) as $conflict)
                <li>{{ $conflict['message'] ?? 'Offline sync conflict' }}</li>
            @endforeach
        </ul>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Resolution Actions</strong></div>
    <div class="card-body d-flex flex-wrap gap-2">
        <form method="post" action="{{ route('sync.conflicts.retry', $record) }}" data-sync-action="retry">@csrf<button class="btn btn-outline-primary btn-sm"><i class="fas fa-rotate me-1"></i>Retry</button></form>
        <form method="post" action="{{ route('sync.conflicts.review', $record) }}" data-sync-action="review">@csrf<input type="hidden" name="reason" value=""><button class="btn btn-outline-success btn-sm"><i class="fas fa-check me-1"></i>Mark Reviewed</button></form>
        <form method="post" action="{{ route('sync.conflicts.cancel', $record) }}" data-sync-action="cancel">@csrf<input type="hidden" name="reason" value=""><button class="btn btn-outline-danger btn-sm"><i class="fas fa-ban me-1"></i>Cancel</button></form>
        <form method="post" action="{{ route('sync.conflicts.override', $record) }}" data-sync-action="override">@csrf<input type="hidden" name="reason" value=""><button class="btn btn-warning btn-sm"><i class="fas fa-shield-halved me-1"></i>Override</button></form>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><a class="text-reset" data-bs-toggle="collapse" href="#payload-json"><strong>Original Payload</strong> <i class="fas fa-chevron-down ms-1"></i></a></div>
            <div id="payload-json" class="collapse">
                <div class="card-body"><pre class="zynq-json mb-0">{{ json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><a class="text-reset" data-bs-toggle="collapse" href="#server-comparison"><strong>Server Comparison</strong> <i class="fas fa-chevron-down ms-1"></i></a></div>
            <div id="server-comparison" class="collapse">
                <div class="card-body"><pre class="zynq-json mb-0">{{ json_encode([
                'branch' => $record->branch?->only(['id', 'branch_name', 'branch_code']),
                'terminal' => $record->terminal?->only(['id', 'terminal_name', 'terminal_code', 'active']),
                'sale' => $record->sale?->only(['id', 'invoice_number', 'status', 'total_amount']),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-sync-action]').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const action = form.dataset.syncAction;
        if (['cancel', 'override', 'review'].includes(action)) {
            const config = {
                cancel: {
                    icon: 'warning',
                    title: 'Cancel offline sale?',
                    text: 'This removes the item from the sync queue and cannot be finalized later.',
                    inputPlaceholder: 'Cancellation reason',
                    confirmButtonText: 'Cancel offline sale',
                    confirmButtonColor: '#dc3545',
                },
                override: {
                    icon: 'warning',
                    title: 'Override conflict?',
                    text: 'A manager reason is required and unsafe conflict types will still be rejected by the server.',
                    inputPlaceholder: 'Manager override reason',
                    confirmButtonText: 'Override',
                    confirmButtonColor: '#ffc107',
                },
                review: {
                    icon: 'question',
                    title: 'Mark conflict reviewed?',
                    text: 'Add a short note for audit history.',
                    inputPlaceholder: 'Review note',
                    confirmButtonText: 'Mark reviewed',
                    confirmButtonColor: '#198754',
                },
            }[action];
            const response = await Swal.fire({
                ...config,
                input: 'text',
                showCancelButton: true,
                preConfirm: value => {
                    if (['cancel', 'override'].includes(action) && !value) {
                        return Swal.showValidationMessage('A reason is required.');
                    }
                    return value || 'Reviewed from conflict detail';
                },
            });
            if (!response.isConfirmed) return;
            form.querySelector('[name="reason"]').value = response.value;
        }
        Swal.fire({title: action === 'retry' ? 'Retrying sync' : 'Updating conflict', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
        try {
            const result = await fetch(form.action, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
                body: new FormData(form),
            });
            const data = await result.json();
            if (!result.ok) throw new Error(data.message || 'Action failed.');
            await Swal.fire({icon: data.status === 'conflict' ? 'warning' : 'success', title: data.status === 'conflict' ? 'Still needs review' : 'Done', text: data.message || 'Conflict updated.'});
            window.location.reload();
        } catch (error) {
            Swal.fire({icon: 'error', title: 'Action failed', text: error.message});
        }
    });
});
</script>
@endpush
@endsection
