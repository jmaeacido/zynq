@extends('layouts.app', ['heading' => 'Compliance Checklist'])

@section('content')
<div class="alert alert-warning">
    BIR-ready, not automatically BIR-approved.
</div>
<div class="card">
    <div class="card-header"><strong>Terminal Setup Readiness</strong></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Tenant</th><th>Branch</th><th>Terminal</th><th>Required setup</th></tr></thead>
            <tbody>
            @forelse ($terminals as $terminal)
                @php($missing = $compliance->missingRequirements($terminal))
                <tr>
                    <td>{{ $terminal->tenant->business_name }}</td>
                    <td>{{ $terminal->branch->branch_name }}</td>
                    <td>{{ $terminal->terminal_name }} ({{ $terminal->terminal_code }})</td>
                    <td>
                        @if ($missing)
                            <span class="badge bg-danger">Blocked for sale</span>
                            {{ implode(', ', $missing) }}
                        @else
                            <span class="badge bg-success">Sale-ready configuration present</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">No terminals configured.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
