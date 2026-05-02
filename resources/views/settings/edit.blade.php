@extends('layouts.app', ['heading' => 'Settings'])

@section('content')
@if ($tenant)
<form method="post" action="{{ route('settings.update') }}" class="card">
    @csrf
    @method('put')
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant</label>
                <select name="tenant_id" class="form-select" required>
                    @foreach ($tenants as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $tenant->id)>{{ $option->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">VAT rate</label>
                <input name="vat_rate" type="number" step="0.01" class="form-control" value="{{ old('vat_rate', data_get($settings->get('vat_rate'), 'value.rate', 12)) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Default invoice title</label>
                <input name="invoice_title_default" class="form-control" value="{{ old('invoice_title_default', data_get($settings->get('invoice_title_default'), 'value.title', 'Sales Invoice')) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Compliance contact email</label>
                <input name="compliance_contact_email" type="email" class="form-control" value="{{ old('compliance_contact_email', data_get($settings->get('compliance_contact_email'), 'value.email')) }}">
            </div>
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Save Settings</button></div>
</form>
@else
    <div class="card"><div class="card-body text-muted">Create a tenant before configuring tenant settings.</div></div>
@endif
@endsection
