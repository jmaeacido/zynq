<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ZYNQ POS' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#">Menu</a></li>
        </ul>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item px-3 align-self-center">{{ auth()->user()->name ?? '' }}</li>
            <li class="nav-item">
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm">Sign out</button>
                </form>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('dashboard') }}" class="brand-link text-center">
            <span class="brand-text fw-bold">ZYNQ</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a></li>
                    @can('manage tenants')
                        <li class="nav-item"><a href="{{ route('tenants.index') }}" class="nav-link">Tenants</a></li>
                    @endcan
                    @can('manage branches')
                        <li class="nav-item"><a href="{{ route('branches.index') }}" class="nav-link">Branches</a></li>
                    @endcan
                    @can('manage terminals')
                        <li class="nav-item"><a href="{{ route('terminals.index') }}" class="nav-link">Terminals</a></li>
                    @endcan
                    @can('manage inventory')
                        <li class="nav-item"><a href="{{ route('categories.index') }}" class="nav-link">Product Categories</a></li>
                        <li class="nav-item"><a href="{{ route('products.index') }}" class="nav-link">Products</a></li>
                        <li class="nav-item"><a href="{{ route('inventory.index') }}" class="nav-link">Inventory</a></li>
                        <li class="nav-item"><a href="{{ route('stock-movements.index') }}" class="nav-link">Stock Movements</a></li>
                    @endcan
                    @can('create sales')
                        <li class="nav-item"><a href="{{ route('cash-sessions.index') }}" class="nav-link">Cash Sessions</a></li>
                        <li class="nav-item"><a href="{{ route('pos.checkout') }}" class="nav-link">POS Checkout</a></li>
                    @endcan
                    @if (auth()->user()?->can('create sales') || auth()->user()?->can('view reports') || auth()->user()?->hasRole('Super Admin'))
                        <li class="nav-item"><a href="{{ route('sales.index') }}" class="nav-link">Sales</a></li>
                    @endif
                    @can('view reports')
                        <li class="nav-item"><a href="{{ route('reports.vat-sales') }}" class="nav-link">VAT Sales Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.non-vat-sales') }}" class="nav-link">Non-VAT Sales Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.discounts') }}" class="nav-link">Discount Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.daily-sales') }}" class="nav-link">Daily Sales Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.voids') }}" class="nav-link">Void Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.refunds') }}" class="nav-link">Refund Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.audit-trail') }}" class="nav-link">Audit Trail</a></li>
                    @endcan
                    @can('manage settings')
                        <li class="nav-item"><a href="{{ route('onboarding.index') }}" class="nav-link">Onboarding Wizard</a></li>
                        <li class="nav-item"><a href="{{ route('settings.bir.edit') }}" class="nav-link">BIR Info Setup</a></li>
                        <li class="nav-item"><a href="{{ route('settings.invoice.edit') }}" class="nav-link">Invoice Settings</a></li>
                        <li class="nav-item"><a href="{{ route('invoice-preview.show') }}" class="nav-link">Invoice Preview</a></li>
                        <li class="nav-item"><a href="{{ route('settings.edit') }}" class="nav-link">Settings</a></li>
                    @endcan
                    @can('view compliance')
                        <li class="nav-item"><a href="{{ route('compliance.checklist') }}" class="nav-link">Compliance</a></li>
                    @endcan
                </ul>
            </nav>
        </div>
    </aside>

    <main class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1 class="m-0">{{ $heading ?? 'ZYNQ' }}</h1>
                <p class="text-muted mb-0">BIR-ready POS foundation for client-specific review, registration, and PTU processing.</p>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <div class="alert alert-warning">
                    This system is BIR-ready but requires client-specific registration and PTU approval.
                </div>
                @auth
                    @php($tenantLicenseStatus = auth()->user()->hasRole('Super Admin') || ! auth()->user()->tenant ? null : app(\App\Domains\Licensing\Services\LicenseService::class)->status(auth()->user()->tenant))
                    @if ($tenantLicenseStatus && $tenantLicenseStatus['warning'])
                        <div class="alert {{ $tenantLicenseStatus['blocked'] ? 'alert-danger' : 'alert-warning' }}">
                            {{ $tenantLicenseStatus['warning'] }}
                        </div>
                    @endif
                @endauth
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Please review the form.</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
