@extends('layouts.app', ['heading' => 'Offline Sync Conflicts'])

@section('content')
<div class="card">
    <div class="card-header"><strong>Manager/Admin Review Required</strong></div>
    <div class="card-body">
        @forelse ($records as $record)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between">
                    <strong>{{ $record->offline_reference }}</strong>
                    <span class="badge bg-danger">{{ $record->status }}</span>
                </div>
                <div class="text-muted mb-2">{{ $record->branch?->branch_name }} / {{ $record->terminal?->terminal_name }} / {{ $record->created_offline_at?->format('Y-m-d H:i') }}</div>
                <ul class="mb-0">
                    @foreach (($record->conflicts ?? []) as $conflict)
                        <li>{{ $conflict['message'] ?? $conflict['code'] ?? 'Offline sync conflict' }}</li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="text-muted mb-0">No offline sync conflicts.</p>
        @endforelse
    </div>
</div>
@endsection
