@extends('layouts.app', ['heading' => 'Branch Setup Wizard'])

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Branches</strong>
        <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">Add Branch</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Tenant</th><th>Branch</th><th>Code</th><th>BIR Registered Address</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($branches as $branch)
                <tr>
                    <td>{{ $branch->tenant->business_name }}</td>
                    <td>{{ $branch->branch_name }}</td>
                    <td>{{ $branch->branch_code }}</td>
                    <td>{{ $branch->bir_registered_address ?: $branch->address }}</td>
                    <td>{{ ucfirst($branch->status) }}</td>
                    <td><a href="{{ route('branches.edit', $branch) }}" class="btn btn-outline-secondary btn-sm">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No branches configured.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $branches->links() }}</div>
</div>
@endsection
