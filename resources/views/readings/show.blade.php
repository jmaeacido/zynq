@extends('layouts.app', ['heading' => $title])

@section('content')
<div class="mb-3">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
</div>
<div class="card">
    <div class="card-body">
        @include('readings.partials.summary', ['reading' => $reading, 'session' => $session])
    </div>
</div>
@endsection
