@extends('layouts.app', ['heading' => 'Cash Sessions'])

@section('content')
<div class="mb-3"><a class="btn btn-primary" href="{{ route('cash-sessions.create') }}">Open Session</a></div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Opened</th><th>Tenant / Branch</th><th>Terminal</th><th>Cashier</th><th>Status</th><th>Expected</th><th>Actual</th><th></th></tr></thead>
            <tbody>
            @forelse ($sessions as $session)
                <tr>
                    <td>{{ $session->opened_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $session->tenant->business_name }}<br><span class="text-muted">{{ $session->branch->branch_name }}</span></td>
                    <td>{{ $session->terminal->terminal_name }}</td>
                    <td>{{ $session->user->name }}</td>
                    <td><span class="badge bg-{{ $session->status === 'open' ? 'success' : 'secondary' }}">{{ $session->status }}</span></td>
                    <td>{{ $session->expected_cash === null ? '-' : number_format((float) $session->expected_cash, 2) }}</td>
                    <td>{{ $session->actual_cash === null ? '-' : number_format((float) $session->actual_cash, 2) }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('cash-sessions.show', $session) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">No cash sessions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $sessions->links() }}
@endsection
