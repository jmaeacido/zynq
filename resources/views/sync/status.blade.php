@extends('layouts.app', ['heading' => 'Offline Sync Status'])

@section('content')
@php
    $counts = [
        'pending' => $records->whereIn('status', ['pending_sync', 'syncing', 'failed'])->count(),
        'synced' => $records->where('status', 'synced')->count(),
        'conflicts' => $records->where('status', 'conflict')->count(),
        'cancelled' => $records->where('status', 'cancelled')->count(),
    ];
    $statusBadge = fn ($status) => match ($status) {
        'synced' => 'success',
        'conflict' => 'danger',
        'cancelled' => 'dark',
        'reviewed' => 'info',
        default => 'warning text-dark',
    };
@endphp
<div class="row g-2 mb-2">
    <div class="col-6 col-lg-3">
        <div class="card zynq-stat border-0 shadow-sm mb-0">
            <div class="card-body py-2">
                <div class="text-muted">Pending</div>
                <div class="h4 mb-0">{{ $counts['pending'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card zynq-stat border-0 shadow-sm mb-0 border-success">
            <div class="card-body py-2">
                <div class="text-muted">Synced</div>
                <div class="h4 mb-0 text-success">{{ $counts['synced'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card zynq-stat border-0 shadow-sm mb-0">
            <div class="card-body py-2">
                <div class="text-muted">Conflicts</div>
                <div class="h4 mb-0 text-danger">{{ $counts['conflicts'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card zynq-stat border-0 shadow-sm mb-0">
            <div class="card-body py-2">
                <div class="text-muted">Cancelled</div>
                <div class="h4 mb-0 text-secondary">{{ $counts['cancelled'] }}</div>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Recent Sync Activity</strong>
        <a class="btn btn-sm btn-outline-danger" href="{{ route('sync.conflicts') }}"><i class="fas fa-triangle-exclamation me-1"></i>Conflicts</a>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Offline Reference</th>
                    <th>Status</th>
                    <th>Branch</th>
                    <th>Terminal</th>
                    <th>Cashier</th>
                    <th>Official Invoice</th>
                    <th>Created Offline</th>
                    <th>Synced</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td><a href="{{ route('sync.conflicts.show', $record) }}">{{ $record->offline_reference }}</a></td>
                        <td><span class="badge bg-{{ $statusBadge($record->status) }}">{{ $record->statusLabel() }}</span></td>
                        <td>{{ $record->branch?->branch_name }}</td>
                        <td>{{ $record->terminal?->terminal_name }}</td>
                        <td>{{ $record->cashier?->name }}</td>
                        <td>
                            @if ($record->sale)
                                <a href="{{ route('sales.invoice.thermal', $record->sale) }}">{{ $record->sale->invoice_number }}</a>
                            @else
                                <span class="text-muted">Pending</span>
                            @endif
                        </td>
                        <td title="{{ $record->created_offline_at?->toDayDateTimeString() }}">{{ $record->created_offline_at?->diffForHumans() }}</td>
                        <td>{{ $record->synced_at?->diffForHumans() ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state"><i class="fas fa-circle-check mb-2 d-block"></i>No offline sync records yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
