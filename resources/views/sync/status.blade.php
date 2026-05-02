@extends('layouts.app', ['heading' => 'Offline Sync Status'])

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Offline Sync Queue</strong>
        <a class="btn btn-sm btn-outline-danger" href="{{ route('sync.conflicts') }}">Conflicts</a>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
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
                        <td><span class="badge bg-{{ $record->status === 'synced' ? 'success' : ($record->status === 'conflict' ? 'danger' : ($record->status === 'cancelled' ? 'dark' : 'secondary')) }}">{{ $record->statusLabel() }}</span></td>
                        <td>{{ $record->branch?->branch_name }}</td>
                        <td>{{ $record->terminal?->terminal_name }}</td>
                        <td>{{ $record->cashier?->name }}</td>
                        <td>
                            @if ($record->sale)
                                <a href="{{ route('sales.invoice.thermal', $record->sale) }}">{{ $record->sale->invoice_number }}</a>
                            @else
                                Pending
                            @endif
                        </td>
                        <td>{{ $record->created_offline_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $record->synced_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-muted">No offline sync records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
