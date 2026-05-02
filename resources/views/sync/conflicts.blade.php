@extends('layouts.app', ['heading' => 'Offline Sync Conflicts'])

@section('content')
<div class="card">
    <div class="card-header"><strong>Manager/Admin Review Required</strong></div>
    <div class="card-body">
        @forelse ($records as $record)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between">
                    <strong><a href="{{ route('sync.conflicts.show', $record) }}">{{ $record->offline_reference }}</a></strong>
                    <span class="badge bg-danger">{{ $record->statusLabel() }}</span>
                </div>
                <div class="text-muted mb-2">{{ $record->branch?->branch_name }} / {{ $record->terminal?->terminal_name }} / {{ $record->cashier?->name }} / {{ $record->created_offline_at?->format('Y-m-d H:i') }}</div>
                <ul class="mb-0">
                    @foreach (($record->conflicts ?? []) as $conflict)
                        <li><code>{{ $conflict['code'] ?? 'conflict' }}</code> {{ $conflict['message'] ?? 'Offline sync conflict' }}</li>
                    @endforeach
                </ul>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <form method="post" action="{{ route('sync.conflicts.retry', $record) }}">@csrf<button class="btn btn-sm btn-outline-primary">Retry Sync</button></form>
                    <form method="post" action="{{ route('sync.conflicts.review', $record) }}">@csrf<input type="hidden" name="reason" value="Reviewed from conflict list"><button class="btn btn-sm btn-outline-secondary">Mark Reviewed</button></form>
                    <form method="post" action="{{ route('sync.conflicts.cancel', $record) }}">@csrf<input type="hidden" name="reason" value="Cancelled from conflict list"><button class="btn btn-sm btn-outline-danger">Cancel Offline Sale</button></form>
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No offline sync conflicts.</p>
        @endforelse
    </div>
</div>
@endsection
