@extends('layouts.app', ['heading' => 'Tenant License'])

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form method="post" action="{{ route('tenants.license.update', $tenant) }}" class="card">
            @csrf
            @method('put')
            <div class="card-header"><strong>{{ $tenant->business_name }}</strong></div>
            <div class="card-body">
                <div class="alert {{ $licenseStatus['blocked'] ? 'alert-danger' : 'alert-info' }}">
                    {{ $licenseStatus['warning'] ?? 'Tenant license is operational.' }}
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">License Key</label>
                        <input name="license_key" value="{{ old('license_key', $tenant->license_key) }}" class="form-control" placeholder="ZYNQ-CLIENT-2026">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Subscription Status</label>
                        <select name="license_status" class="form-select">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('license_status', $tenant->license_status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Subscription Expiry</label>
                        <input type="date" name="subscription_expires_at" value="{{ old('subscription_expires_at', optional($tenant->subscription_expires_at)->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Grace Period Days</label>
                        <input type="number" name="grace_period_days" min="0" max="365" value="{{ old('grace_period_days', $tenant->grace_period_days ?? 0) }}" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" value="1" class="form-check-input" id="active" @checked(old('active', $tenant->active))>
                            <label for="active" class="form-check-label">Tenant active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Save License</button></div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><strong>Operating Rule</strong></div>
            <div class="card-body">
                Disabled, suspended, inactive, or expired tenants past grace period are blocked from tenant operations. Super Admin users can still manage tenants and license records.
            </div>
        </div>
    </div>
</div>
@endsection
