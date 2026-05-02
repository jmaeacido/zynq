@extends('layouts.app', ['heading' => $tenant->exists ? 'Edit Tenant' : 'New Tenant'])

@section('content')
<form method="post" action="{{ $tenant->exists ? route('tenants.update', $tenant) : route('tenants.store') }}" class="card">
    @csrf
    @if ($tenant->exists) @method('put') @endif
    <div class="card-body">
        <div class="row">
            @include('tenants.partials.fields')
        </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary">Save Tenant</button></div>
</form>
@endsection
