@extends('layouts.app', ['heading' => 'Terminal Setup Wizard'])

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Terminals</strong>
        <a href="{{ route('terminals.create') }}" class="btn btn-primary btn-sm">Add Terminal</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Tenant</th><th>Branch</th><th>Terminal</th><th>MIN</th><th>PTU</th><th>Readiness</th><th></th></tr></thead>
            <tbody>
            @forelse ($terminals as $terminal)
                @php($missing = $compliance->missingRequirements($terminal))
                <tr>
                    <td>{{ $terminal->tenant->business_name }}</td>
                    <td>{{ $terminal->branch->branch_name }}</td>
                    <td>{{ $terminal->terminal_name }} ({{ $terminal->terminal_code }})</td>
                    <td>{{ $terminal->machine_identification_number ?: 'Missing' }}</td>
                    <td>{{ $terminal->permit_to_use_number ?: 'Missing' }}</td>
                    <td>
                        @if ($missing)
                            <span class="badge bg-danger">Blocked</span> {{ implode(', ', $missing) }}
                        @else
                            <span class="badge bg-success">Ready</span>
                        @endif
                    </td>
                    <td><a href="{{ route('terminals.edit', $terminal) }}" class="btn btn-outline-secondary btn-sm">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No terminals configured.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $terminals->links() }}</div>
</div>
@endsection
