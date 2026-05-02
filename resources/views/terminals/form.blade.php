@extends('layouts.app', ['heading' => $terminal->exists ? 'Edit Terminal' : 'New Terminal'])

@section('content')
@if (! empty($missing))
    <div class="alert alert-danger">Sales must be blocked for this terminal until these fields are configured: {{ implode(', ', $missing) }}.</div>
@endif
<form method="post" action="{{ $terminal->exists ? route('terminals.update', $terminal) : route('terminals.store') }}" class="card">
    @csrf
    @if ($terminal->exists) @method('put') @endif
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant</label>
                <select name="tenant_id" class="form-select" required>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}" @selected((int) old('tenant_id', $terminal->tenant_id) === $tenant->id)>{{ $tenant->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select" required>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((int) old('branch_id', $terminal->branch_id) === $branch->id)>{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3"><label class="form-label">Terminal name</label><input name="terminal_name" class="form-control" value="{{ old('terminal_name', $terminal->terminal_name) }}" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Terminal code</label><input name="terminal_code" class="form-control" value="{{ old('terminal_code', $terminal->terminal_code) }}" required></div>
            <div class="col-md-6 mb-3"><label class="form-label">Machine Identification Number / MIN</label><input name="machine_identification_number" class="form-control" value="{{ old('machine_identification_number', $terminal->machine_identification_number) }}"></div>
            <div class="col-md-6 mb-3"><label class="form-label">Permit to Use / PTU number</label><input name="permit_to_use_number" class="form-control" value="{{ old('permit_to_use_number', $terminal->permit_to_use_number) }}"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Serial number</label><input name="serial_number" class="form-control" value="{{ old('serial_number', $terminal->serial_number) }}" required></div>
            <div class="col-md-4 mb-3"><label class="form-label">Accreditation number</label><input name="accreditation_number" class="form-control" value="{{ old('accreditation_number', $terminal->accreditation_number) }}"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Software version</label><input name="software_version" class="form-control" value="{{ old('software_version', $terminal->software_version) }}" required></div>
            <div class="col-md-12 mb-3"><div class="form-check"><input type="hidden" name="active" value="0"><input id="active" name="active" value="1" type="checkbox" class="form-check-input" @checked(old('active', $terminal->active ?? true))><label for="active" class="form-check-label">Active terminal</label></div></div>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Save Terminal</button></div>
</form>
@endsection
