@extends('layouts.app', ['heading' => 'Invoice Branding & Footer'])

@section('content')
@if ($tenant)
<form method="post" action="{{ route('settings.invoice.update') }}" class="card" enctype="multipart/form-data">
    @csrf
    @method('put')
    <div class="card-header"><strong>{{ $tenant->business_name }}</strong></div>
    <div class="card-body">
        <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Tenant</label>
                <select class="form-select" onchange="window.location='{{ route('settings.invoice.edit') }}?tenant_id='+this.value">
                    @foreach ($tenants as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $tenant->id)>{{ $option->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Trade Name</label>
                <input name="trade_name" value="{{ old('trade_name', $tenant->trade_name) }}" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Logo Upload</label>
                <input type="file" name="logo" accept="image/*" class="form-control">
                @if ($tenant->logo_path)
                    <div class="form-text">Current logo: {{ $tenant->logo_path }}</div>
                @endif
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Invoice Footer</label>
                <textarea name="invoice_footer" class="form-control" rows="4">{{ old('invoice_footer', $tenant->invoice_footer) }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary">Save Invoice Settings</button>
        <a href="{{ route('invoice-preview.show') }}" class="btn btn-outline-secondary">Preview Invoice</a>
    </div>
</form>
@else
    <div class="alert alert-info">No tenant available for setup.</div>
@endif
@endsection
