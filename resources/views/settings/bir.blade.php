@extends('layouts.app', ['heading' => 'BIR Information Setup'])

@section('content')
@if ($tenant)
<form method="post" action="{{ route('settings.bir.update') }}" class="card">
    @csrf
    @method('put')
    <div class="card-header"><strong>Client BIR Registration Details</strong></div>
    <div class="card-body">
        <div class="alert alert-warning">BIR-ready, not automatically BIR-approved.</div>
        <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant</label>
                <select class="form-select" onchange="window.location='{{ route('settings.bir.edit') }}?tenant_id='+this.value">
                    @foreach ($tenants as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $tenant->id)>{{ $option->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">TIN</label>
                <input name="tin" value="{{ old('tin', $tenant->tin) }}" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Taxpayer Type</label>
                <select name="taxpayer_type" class="form-select">
                    <option value="VAT" @selected(old('taxpayer_type', $tenant->taxpayer_type) === 'VAT')>VAT</option>
                    <option value="NON_VAT" @selected(old('taxpayer_type', $tenant->taxpayer_type) === 'NON_VAT')>NON-VAT</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">BIR RDO Code</label>
                <input name="bir_rdo_code" value="{{ old('bir_rdo_code', $tenant->bir_rdo_code) }}" class="form-control" required>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Registered Address</label>
                <textarea name="registered_address" class="form-control" rows="3" required>{{ old('registered_address', $tenant->registered_address) }}</textarea>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Registration Notes</label>
                <textarea name="bir_registration_notes" class="form-control" rows="3">{{ old('bir_registration_notes', $tenant->settings->firstWhere('key', 'bir_registration_notes')?->value['notes'] ?? '') }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Save BIR Info</button></div>
</form>
@else
    <div class="alert alert-info">No tenant available for setup.</div>
@endif
@endsection
