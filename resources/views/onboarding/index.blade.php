@extends('layouts.app', ['heading' => 'Tenant Onboarding'])

@section('content')
@foreach ($tenants as $tenant)
    @php
        $terminalIssues = $tenant->terminals->flatMap(fn ($terminal) => $compliance->missingRequirements($terminal))->unique()->values();
    @endphp
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ $tenant->business_name }}</strong>
            @if ($tenant->onboarding_completed_at)
                <span class="badge bg-success">Completed {{ $tenant->onboarding_completed_at->format('Y-m-d') }}</span>
            @else
                <span class="badge bg-warning text-dark">In setup</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>BIR Info</strong><br><span class="{{ $tenant->tin && $tenant->bir_rdo_code ? 'text-success' : 'text-danger' }}">{{ $tenant->tin && $tenant->bir_rdo_code ? 'Configured' : 'Incomplete' }}</span></div>
                <div class="col-md-3"><strong>Branches</strong><br>{{ $tenant->branches->count() }} configured</div>
                <div class="col-md-3"><strong>Terminals</strong><br>{{ $tenant->terminals->count() }} configured</div>
                <div class="col-md-3"><strong>Compliance</strong><br>{{ $terminalIssues->isEmpty() ? 'Terminal fields ready' : 'Missing: '.$terminalIssues->implode(', ') }}</div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-3"><strong>Invoice Footer</strong><br><span class="{{ $tenant->invoice_footer ? 'text-success' : 'text-danger' }}">{{ $tenant->invoice_footer ? 'Configured' : 'Pending' }}</span></div>
                <div class="col-md-3"><strong>License</strong><br><span class="{{ in_array($tenant->license_status, ['active', 'trial', 'grace_period'], true) ? 'text-success' : 'text-danger' }}">{{ ucfirst(str_replace('_', ' ', $tenant->license_status)) }}</span></div>
                <div class="col-md-3"><strong>Artifacts</strong><br><span class="{{ $requiredDocsReady ? 'text-success' : 'text-danger' }}">{{ $requiredDocsReady ? 'Docs and samples present' : 'Docs or samples missing' }}</span></div>
                <div class="col-md-3"><strong>Final Review</strong><br>CPA/BIR/RDO confirmation required</div>
            </div>
        </div>
        <div class="card-footer d-flex gap-2 flex-wrap">
            <a href="{{ route('settings.bir.edit') }}" class="btn btn-outline-primary btn-sm">BIR Info</a>
            <a href="{{ route('onboarding.branches') }}" class="btn btn-outline-primary btn-sm">Branch Setup</a>
            <a href="{{ route('onboarding.terminals') }}" class="btn btn-outline-primary btn-sm">Terminal Setup</a>
            <a href="{{ route('settings.invoice.edit') }}" class="btn btn-outline-primary btn-sm">Invoice Branding</a>
            <a href="{{ route('invoice-preview.show') }}" class="btn btn-outline-secondary btn-sm">Invoice Preview</a>
            <form method="post" action="{{ route('onboarding.complete', $tenant) }}">
                @csrf
                <button class="btn btn-success btn-sm">Mark Complete</button>
            </form>
        </div>
    </div>
@endforeach
@if ($tenants->isEmpty())
    <div class="alert alert-info">No tenants available for setup.</div>
@endif
@endsection
