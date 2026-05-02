@extends('layouts.app', ['heading' => 'Invoice Preview Generator'])

@section('content')
@if ($tenant)
<form method="get" action="{{ route('invoice-preview.show') }}" class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Tenant</label>
                <select name="tenant_id" class="form-select">
                    @foreach ($tenants as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $tenant->id)>{{ $option->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select">
                    @foreach ($branches as $option)
                        <option value="{{ $option->id }}" @selected($branch && $option->id === $branch->id)>{{ $option->branch_name }} ({{ $option->branch_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Terminal</label>
                <select name="terminal_id" class="form-select">
                    @foreach ($terminals as $option)
                        <option value="{{ $option->id }}" @selected($terminal && $option->id === $terminal->id)>{{ $option->terminal_name }} ({{ $option->terminal_code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Template</label>
                <select name="template" class="form-select">
                    <option value="thermal" @selected($template === 'thermal')>Thermal</option>
                    <option value="a4" @selected($template === 'a4')>A4</option>
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100">View</button>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-lg-8">
        <div class="card {{ $template === 'thermal' ? 'mx-auto' : '' }}" style="{{ $template === 'thermal' ? 'max-width: 380px;' : '' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Sample {{ strtoupper($template) }} Sales Invoice</strong>
                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">Print Preview</button>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="mb-0">{{ $tenant->business_name }}</h4>
                    <div>{{ $tenant->trade_name }}</div>
                    <div>{{ $tenant->registered_address }}</div>
                    <div>TIN: {{ $tenant->tin }} | RDO: {{ $tenant->bir_rdo_code }}</div>
                </div>
                <div class="row small mb-3">
                    <div class="col-md-6">
                        <strong>Branch:</strong> {{ $branch?->branch_code ?? 'BR-SETUP' }}<br>
                        <strong>Terminal:</strong> {{ $terminal?->terminal_code ?? 'TERM-SETUP' }}<br>
                        <strong>MIN:</strong> {{ $terminal?->machine_identification_number ?? 'Pending setup' }}
                    </div>
                    <div class="col-md-6 text-md-end">
                        <strong>PTU:</strong> {{ $terminal?->permit_to_use_number ?? 'Pending setup' }}<br>
                        <strong>Accreditation:</strong> {{ $terminal?->accreditation_number ?? 'Pending setup' }}<br>
                        <strong>Software:</strong> {{ $terminal?->software_version ?? config('app.version', 'ZYNQ') }}
                    </div>
                </div>
                <h5 class="text-center">Sales Invoice</h5>
                <div class="mb-2"><strong>Invoice No:</strong> PREVIEW-000001</div>
                <table class="table table-sm">
                    <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Unit</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td class="text-end">{{ $item['quantity'] }}</td>
                            <td class="text-end">{{ number_format($item['unit_price'], 2) }}</td>
                            <td class="text-end">{{ number_format($item['total'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="3" class="text-end">Gross Sales</th><th class="text-end">250.00</th></tr>
                        <tr><th colspan="3" class="text-end">VATable Sales</th><th class="text-end">178.57</th></tr>
                        <tr><th colspan="3" class="text-end">VAT Amount</th><th class="text-end">21.43</th></tr>
                        <tr><th colspan="3" class="text-end">VAT-Exempt Sales</th><th class="text-end">50.00</th></tr>
                        <tr><th colspan="3" class="text-end">Net Amount Due</th><th class="text-end">250.00</th></tr>
                    </tfoot>
                </table>
                <div class="text-center small">{{ $tenant->invoice_footer }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="alert alert-warning">BIR-ready, not automatically BIR-approved.</div>
        <div class="card">
            <div class="card-header"><strong>Preview Notes</strong></div>
            <div class="card-body">
                This preview uses configured tenant, branch, terminal, and footer data with sample line items for review before actual BIR/RDO/CPA validation.
            </div>
        </div>
    </div>
</div>
@else
    <div class="alert alert-info">No tenant available for invoice preview.</div>
@endif
@endsection
