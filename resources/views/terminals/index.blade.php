@extends('layouts.app', ['heading' => 'Terminals'])

@section('content')
<div class="mb-3"><a class="btn btn-primary" href="{{ route('terminals.create') }}">New Terminal</a></div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Terminal</th><th>Tenant / Branch</th><th>Compliance</th><th>Active</th><th></th></tr></thead>
            <tbody>
            @foreach ($terminals as $terminal)
                @php($missing = $compliance->missingRequirements($terminal))
                <tr>
                    <td>{{ $terminal->terminal_name }}<br><span class="text-muted">{{ $terminal->terminal_code }}</span></td>
                    <td>{{ $terminal->tenant->business_name }}<br><span class="text-muted">{{ $terminal->branch->branch_name }}</span></td>
                    <td>
                        @if ($missing)
                            <span class="badge bg-danger">Incomplete</span>
                            <span class="text-muted">{{ implode(', ', $missing) }}</span>
                        @else
                            <span class="badge bg-success">Sale-ready</span>
                        @endif
                    </td>
                    <td>{{ $terminal->active ? 'Yes' : 'No' }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('terminals.edit', $terminal) }}">Edit</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $terminals->links() }}
@endsection
