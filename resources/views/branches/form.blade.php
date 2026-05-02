@extends('layouts.app', ['heading' => $branch->exists ? 'Edit Branch' : 'New Branch'])

@section('content')
<form method="post" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}" class="card">
    @csrf
    @if ($branch->exists) @method('put') @endif
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant</label>
                <select name="tenant_id" class="form-select" required>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((int) old('tenant_id', $branch->tenant_id) === $tenant->id)>{{ $tenant->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3"><label class="form-label">Branch name</label><input name="branch_name" class="form-control" value="{{ old('branch_name', $branch->branch_name) }}" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Branch code</label><input name="branch_code" class="form-control" value="{{ old('branch_code', $branch->branch_code) }}" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" @selected(old('status', $branch->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $branch->status) === 'inactive')>Inactive</option></select></div>
            <div class="col-md-12 mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" required>{{ old('address', $branch->address) }}</textarea></div>
            <div class="col-md-12 mb-3"><label class="form-label">BIR registered address</label><textarea name="bir_registered_address" class="form-control">{{ old('bir_registered_address', $branch->bir_registered_address) }}</textarea></div>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Save Branch</button></div>
</form>
@endsection
