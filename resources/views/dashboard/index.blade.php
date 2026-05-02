@extends('layouts.app', ['heading' => auth()->user()->hasRole('Super Admin') ? 'Super Admin Dashboard' : 'Tenant Dashboard'])

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $tenantCount }}</h3><p>Tenants</p></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $branchCount }}</h3><p>Branches</p></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner"><h3>{{ $terminalCount }}</h3><p>Terminals</p></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Terminal Compliance Warnings</strong></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Terminal</th><th>Tenant</th><th>Branch</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($terminals as $terminal)
                @php($missing = $compliance->missingRequirements($terminal))
                <tr>
                    <td>{{ $terminal->terminal_name }} <span class="text-muted">({{ $terminal->terminal_code }})</span></td>
                    <td>{{ $terminal->tenant->business_name }}</td>
                    <td>{{ $terminal->branch->branch_name }}</td>
                    <td>
                        @if ($missing)
                            <span class="badge bg-danger">Setup incomplete</span>
                            <span class="text-muted">{{ implode(', ', $missing) }}</span>
                        @else
                            <span class="badge bg-success">Sale-ready</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">No terminals configured yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
