<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ZYNQ POS' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body { font-size: .92rem; }
        .content-header { padding: .75rem .5rem; }
        .content-header h1 { font-size: 1.35rem; font-weight: 700; }
        .content-header p { font-size: .82rem; }
        .content { padding-bottom: 1rem; }
        .card { border-radius: .35rem; }
        .card-header { padding: .55rem .8rem; }
        .card-body { padding: .8rem; }
        .table td, .table th { padding: .45rem .55rem; vertical-align: middle; }
        .table-sm td, .table-sm th { padding: .28rem .4rem; }
        .badge { font-size: .72rem; font-weight: 600; padding: .28em .5em; }
        .btn-sm { padding: .25rem .5rem; }
        .form-label { margin-bottom: .25rem; font-size: .78rem; font-weight: 600; color: #495057; }
        .form-control, .form-select { font-size: .9rem; }
        .alert { padding: .6rem .8rem; }
        .zynq-stat { border-left: 3px solid #6c757d; }
        .zynq-stat .text-muted { font-size: .73rem; text-transform: uppercase; letter-spacing: 0; }
        .zynq-stat .h4 { font-size: 1.35rem; }
        .compact-meta { font-size: .82rem; color: #6c757d; }
        .sticky-actions { position: sticky; right: 0; background: inherit; box-shadow: -8px 0 12px rgba(255,255,255,.85); }
        .empty-state { padding: 2rem 1rem; text-align: center; color: #6c757d; }
        .nav-sidebar .nav-link { padding: .45rem .75rem; }
        .nav-sidebar .nav-icon { width: 1.35rem; }
        pre.zynq-json { max-height: 26rem; overflow: auto; white-space: pre-wrap; font-size: .78rem; }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @php
        $syncPendingCount = 0;
        $syncConflictCount = 0;
        if (auth()->check()) {
            $syncCountQuery = \App\Domains\Sales\Models\OfflineSaleSyncRecord::query();
            if (! auth()->user()->hasRole('Super Admin')) {
                $syncCountQuery->where('tenant_id', auth()->user()->tenant_id);
                if (auth()->user()->branch_id) {
                    $syncCountQuery->where('branch_id', auth()->user()->branch_id);
                }
            }
            $syncPendingCount = (clone $syncCountQuery)->whereIn('status', ['pending_sync', 'syncing', 'failed'])->count();
            $syncConflictCount = (clone $syncCountQuery)->where('status', 'conflict')->count();
        }
    @endphp
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" aria-label="Toggle navigation"><i class="fas fa-bars"></i></a></li>
        </ul>
        <ul class="navbar-nav ms-auto">
            @auth
                <li class="nav-item px-3 align-self-center">
                    <span id="zynq-offline-indicator" class="badge bg-success"><i class="fas fa-wifi me-1"></i>Online</span>
                    <span id="zynq-pending-sync" class="badge bg-secondary">{{ $syncPendingCount }} pending</span>
                </li>
            @endauth
            <li class="nav-item px-3 align-self-center">{{ auth()->user()->name ?? '' }}</li>
            <li class="nav-item">
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-right-from-bracket me-1"></i>Sign out</button>
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
                    <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link"><i class="nav-icon fas fa-gauge"></i><p>Dashboard</p></a></li>
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
                        <li class="nav-item"><a href="{{ route('products.index') }}" class="nav-link" data-offline-unsupported>Products</a></li>
                        <li class="nav-item"><a href="{{ route('inventory.index') }}" class="nav-link" data-offline-unsupported>Inventory</a></li>
                        <li class="nav-item"><a href="{{ route('stock-movements.index') }}" class="nav-link" data-offline-unsupported>Stock Movements</a></li>
                    @endcan
                    @can('create sales')
                        <li class="nav-item"><a href="{{ route('cash-sessions.index') }}" class="nav-link"><i class="nav-icon fas fa-cash-register"></i><p>Cash Sessions</p></a></li>
                        <li class="nav-item"><a href="{{ route('pos.checkout') }}" class="nav-link"><i class="nav-icon fas fa-cart-shopping"></i><p>POS Checkout</p></a></li>
                        <li class="nav-item"><a href="{{ route('sync.status') }}" class="nav-link"><i class="nav-icon fas fa-rotate"></i><p>Offline Sync Status @if($syncPendingCount)<span class="right badge bg-warning text-dark">{{ $syncPendingCount }}</span>@endif</p></a></li>
                    @endcan
                    @can('view reports')
                        <li class="nav-item"><a href="{{ route('sync.conflicts') }}" class="nav-link"><i class="nav-icon fas fa-triangle-exclamation"></i><p>Offline Sync Conflicts @if($syncConflictCount)<span class="right badge bg-danger">{{ $syncConflictCount }}</span>@endif</p></a></li>
                    @endcan
                    @if (auth()->user()?->can('create sales') || auth()->user()?->can('view reports') || auth()->user()?->hasRole('Super Admin'))
                        <li class="nav-item"><a href="{{ route('sales.index') }}" class="nav-link">Sales</a></li>
                    @endif
                    @can('view reports')
                        <li class="nav-item"><a href="{{ route('reports.vat-sales') }}" class="nav-link" data-offline-unsupported>VAT Sales Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.non-vat-sales') }}" class="nav-link" data-offline-unsupported>Non-VAT Sales Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.discounts') }}" class="nav-link" data-offline-unsupported>Discount Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.daily-sales') }}" class="nav-link" data-offline-unsupported>Daily Sales Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.voids') }}" class="nav-link" data-offline-unsupported>Void Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.refunds') }}" class="nav-link" data-offline-unsupported>Refund Report</a></li>
                        <li class="nav-item"><a href="{{ route('reports.audit-trail') }}" class="nav-link" data-offline-unsupported>Audit Trail</a></li>
                    @endcan
                    @can('manage settings')
                        <li class="nav-item"><a href="{{ route('onboarding.index') }}" class="nav-link">Onboarding Wizard</a></li>
                        <li class="nav-item"><a href="{{ route('settings.bir.edit') }}" class="nav-link">BIR Info Setup</a></li>
                        <li class="nav-item"><a href="{{ route('settings.invoice.edit') }}" class="nav-link">Invoice Settings</a></li>
                        <li class="nav-item"><a href="{{ route('invoice-preview.show') }}" class="nav-link">Invoice Preview</a></li>
                        <li class="nav-item"><a href="{{ route('settings.edit') }}" class="nav-link" data-offline-unsupported>Settings</a></li>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.ZynqOfflineShell = (() => {
    const dbName = 'zynq-pos-offline';
    const version = 1;
    const openDb = () => new Promise((resolve, reject) => {
        const request = indexedDB.open(dbName, version);
        request.onupgradeneeded = event => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('offline_sales')) db.createObjectStore('offline_sales', {keyPath: 'idempotency_key'});
            if (!db.objectStoreNames.contains('snapshots')) db.createObjectStore('snapshots', {keyPath: 'key'});
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
    const allSales = async () => {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('offline_sales', 'readonly');
            const req = tx.objectStore('offline_sales').getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    };
    const refresh = async () => {
        const indicator = document.getElementById('zynq-offline-indicator');
        const pending = document.getElementById('zynq-pending-sync');
        if (indicator) {
            indicator.innerHTML = navigator.onLine ? '<i class="fas fa-wifi me-1"></i>Online' : '<i class="fas fa-plug-circle-xmark me-1"></i>Offline';
            indicator.className = 'badge ' + (navigator.onLine ? 'bg-success' : 'bg-danger');
        }
        if (pending) {
            const count = (await allSales()).filter(item => ['pending_sync', 'sync_failed', 'conflict'].includes(item.sync_status)).length;
            pending.textContent = count + ' pending';
            pending.className = 'badge ' + (count > 0 ? 'bg-warning text-dark' : 'bg-secondary');
        }
        document.querySelectorAll('[data-offline-unsupported]').forEach(link => {
            if (navigator.onLine) {
                link.classList.remove('disabled');
                link.removeAttribute('title');
            } else {
                link.classList.add('disabled');
                link.setAttribute('title', 'Unavailable while offline. Return online to continue.');
            }
        });
    };
    window.addEventListener('online', refresh);
    window.addEventListener('offline', refresh);
    document.addEventListener('DOMContentLoaded', refresh);
    return {openDb, allSales, refresh};
})();
</script>
@stack('scripts')
</body>
</html>
