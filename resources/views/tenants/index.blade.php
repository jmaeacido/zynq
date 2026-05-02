@extends('layouts.app', ['heading' => 'Tenants'])

@section('content')
<div class="mb-3"><a class="btn btn-primary" href="{{ route('tenants.create') }}">New Tenant</a></div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Business</th><th>TIN</th><th>Taxpayer</th><th>License</th><th></th></tr></thead>
            <tbody>
            @foreach ($tenants as $tenant)
                <tr>
                    <td>{{ $tenant->business_name }}<br><span class="text-muted">{{ $tenant->trade_name }}</span></td>
                    <td>{{ $tenant->tin }}</td>
                    <td>{{ $tenant->taxpayer_type }}</td>
                    <td>
                        {{ $tenant->license_status }}
                        @if ($tenant->subscription_expires_at)
                            <br><span class="text-muted">Expires {{ $tenant->subscription_expires_at->format('Y-m-d') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('tenants.edit', $tenant) }}">Edit</a>
                        @can('manage licenses')
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('tenants.license.edit', $tenant) }}">License</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{ $tenants->links() }}
@endsection
