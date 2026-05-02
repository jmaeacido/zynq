@extends('layouts.app', ['heading' => 'Branches'])

@section('content')
<div class="mb-3"><a class="btn btn-primary" href="{{ route('branches.create') }}">New Branch</a></div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Branch</th><th>Tenant</th><th>Code</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($branches as $branch)
                <tr>
                    <td>{{ $branch->branch_name }}<br><span class="text-muted">{{ $branch->address }}</span></td>
                    <td>{{ $branch->tenant->business_name }}</td>
                    <td>{{ $branch->branch_code }}</td>
                    <td><span class="badge bg-{{ $branch->status === 'active' ? 'success' : 'secondary' }}">{{ $branch->status }}</span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('branches.edit', $branch) }}">Edit</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $branches->links() }}
@endsection
