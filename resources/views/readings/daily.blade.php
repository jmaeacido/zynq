@extends('layouts.app', ['heading' => 'Daily Sales Report'])

@section('content')
<div class="mb-3">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
</div>
<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Sales Count</dt><dd class="col-sm-8">{{ $reading['sale_count'] }}</dd>
            <dt class="col-sm-4">Gross Sales</dt><dd class="col-sm-8">{{ number_format($reading['gross_sales'], 2) }}</dd>
            <dt class="col-sm-4">Net Sales</dt><dd class="col-sm-8">{{ number_format($reading['net_sales'], 2) }}</dd>
        </dl>
    </div>
</div>
@endsection
