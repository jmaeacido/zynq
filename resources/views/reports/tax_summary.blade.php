@extends('layouts.app', ['heading' => $heading])

@section('content')
<div class="mb-3">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            @foreach ($totals as $key => $value)
                <tr><th>{{ ucwords(str_replace('_', ' ', $key)) }}</th><td class="text-end">{{ number_format((float) $value, 2) }}</td></tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
