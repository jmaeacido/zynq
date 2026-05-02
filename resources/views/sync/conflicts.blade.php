@extends('layouts.app', ['heading' => 'Offline Sync Conflicts'])

@section('content')
@php
    $unsafeCodes = ['tenant_mismatch', 'terminal_mismatch', 'duplicate_idempotency_abuse', 'payload_hash_mismatch', 'terminal_not_compliant', 'offline_invoice_range_invalid'];
@endphp
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Manager/Admin Review Required</strong>
        <span class="badge bg-danger">{{ $records->count() }} conflict{{ $records->count() === 1 ? '' : 's' }}</span>
    </div>
    <div class="card-body p-0">
        @forelse ($records as $record)
            @php
                $codes = collect($record->conflicts ?? [])->pluck('code')->filter();
                $unsafe = $codes->intersect($unsafeCodes)->isNotEmpty();
            @endphp
            <div class="border-bottom p-2">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <strong><a href="{{ route('sync.conflicts.show', $record) }}">{{ $record->offline_reference }}</a></strong>
                        <span class="badge bg-danger ms-1">{{ $record->statusLabel() }}</span>
                        <span class="badge bg-{{ $unsafe ? 'dark' : 'info' }} ms-1">{{ $unsafe ? 'Unsafe' : 'Reviewable' }}</span>
                    </div>
                    <div class="compact-meta">
                        {{ $record->created_offline_at?->diffForHumans() }}
                    </div>
                </div>
                <div class="compact-meta mb-1">{{ $record->branch?->branch_name }} / {{ $record->terminal?->terminal_name }} / {{ $record->cashier?->name }}</div>
                <div class="d-flex flex-wrap gap-1 mb-1">
                    @foreach ($codes as $code)
                        <span class="badge bg-{{ in_array($code, $unsafeCodes, true) ? 'dark' : 'secondary' }}">{{ $code }}</span>
                    @endforeach
                </div>
                <ul class="mb-2 small ps-3">
                    @foreach (($record->conflicts ?? []) as $conflict)
                        <li>{{ $conflict['message'] ?? 'Offline sync conflict' }}</li>
                    @endforeach
                </ul>
                <div class="d-flex flex-wrap gap-2">
                    <form method="post" action="{{ route('sync.conflicts.retry', $record) }}" data-sync-action="retry">@csrf<button class="btn btn-sm btn-outline-primary"><i class="fas fa-rotate me-1"></i>Retry</button></form>
                    <form method="post" action="{{ route('sync.conflicts.review', $record) }}" data-sync-action="review">@csrf<input type="hidden" name="reason" value="Reviewed from conflict list"><button class="btn btn-sm btn-outline-success"><i class="fas fa-check me-1"></i>Mark Reviewed</button></form>
                    <form method="post" action="{{ route('sync.conflicts.cancel', $record) }}" data-sync-action="cancel">@csrf<input type="hidden" name="reason" value=""><button class="btn btn-sm btn-outline-danger"><i class="fas fa-ban me-1"></i>Cancel</button></form>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('sync.conflicts.show', $record) }}"><i class="fas fa-eye me-1"></i>Review</a>
                </div>
            </div>
        @empty
            <div class="empty-state"><i class="fas fa-circle-check mb-2 d-block"></i>No offline sync conflicts.</div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-sync-action]').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const action = form.dataset.syncAction;
        if (action === 'cancel') {
            const response = await Swal.fire({
                icon: 'warning',
                title: 'Cancel offline sale?',
                text: 'This removes the item from the sync queue and cannot be finalized later.',
                input: 'text',
                inputPlaceholder: 'Cancellation reason',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Cancel offline sale',
                preConfirm: value => value || Swal.showValidationMessage('Cancellation reason is required.'),
            });
            if (!response.isConfirmed) return;
            form.querySelector('[name="reason"]').value = response.value;
        } else if (action === 'review') {
            const response = await Swal.fire({
                icon: 'question',
                title: 'Mark conflict reviewed?',
                showCancelButton: true,
                confirmButtonText: 'Mark reviewed',
            });
            if (!response.isConfirmed) return;
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
