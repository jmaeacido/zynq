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
        .nav-sidebar .nav-treeview .nav-link { padding-left: 1.25rem; font-size: .88rem; }
        .nav-sidebar .nav-treeview .nav-icon { font-size: .72rem; }
        .nav-sidebar .nav-link.active { box-shadow: inset 3px 0 rgba(255,255,255,.85); }
        .nav-sidebar .badge.right { right: .75rem; top: .55rem; }
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
        $active = fn (array $patterns) => request()->routeIs(...$patterns);
        $activeClass = fn (array $patterns) => $active($patterns) ? 'active' : '';
        $treeClass = fn (array $patterns) => $active($patterns) ? 'menu-open' : '';
        $treeLinkClass = fn (array $patterns) => $active($patterns) ? 'active' : '';
        $user = auth()->user();
        $canSalesNav = $user?->can('create sales') || $user?->can('view reports') || $user?->hasRole('Super Admin');
        $canSyncNav = $user?->can('create sales') || $user?->can('view reports') || $user?->hasRole('Super Admin');
        $canSetupNav = $user?->can('manage tenants') || $user?->can('manage branches') || $user?->can('manage terminals') || $user?->can('manage settings') || $user?->can('view compliance');
        $canAdminNav = $user?->can('manage licenses') || $user?->can('manage settings');
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
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ $activeClass(['dashboard']) }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                        </a>
                    </li>

                    @if ($canSalesNav)
                        <li class="nav-item has-treeview {{ $treeClass(['pos.*', 'sales.*', 'cash-sessions.*', 'readings.*']) }}">
                            <a href="#" class="nav-link {{ $treeLinkClass(['pos.*', 'sales.*', 'cash-sessions.*', 'readings.*']) }}" role="button">
                                <i class="nav-icon fas fa-cash-register"></i><p>POS Operations <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('create sales')
                                    <li class="nav-item"><a href="{{ route('pos.checkout') }}" class="nav-link {{ $activeClass(['pos.*']) }}"><i class="far fa-circle nav-icon"></i><p>POS Checkout</p></a></li>
                                @endcan
                                @if ($canSalesNav)
                                    <li class="nav-item"><a href="{{ route('sales.index') }}" class="nav-link {{ $activeClass(['sales.index', 'sales.show']) }}"><i class="fas fa-receipt nav-icon"></i><p>Sales</p></a></li>
                                @endif
                                @can('create sales')
                                    <li class="nav-item"><a href="{{ route('cash-sessions.index') }}" class="nav-link {{ $activeClass(['cash-sessions.*', 'readings.*']) }}"><i class="fas fa-clock nav-icon"></i><p>Cash Sessions / Readings</p></a></li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if ($canSyncNav)
                        <li class="nav-item has-treeview {{ $treeClass(['sync.*']) }}">
                            <a href="#" class="nav-link {{ $treeLinkClass(['sync.*']) }}" role="button">
                                <i class="nav-icon fas fa-sync-alt"></i><p>Offline Sync <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('create sales')
                                    <li class="nav-item">
                                        <a href="{{ route('sync.status') }}" class="nav-link {{ $activeClass(['sync.status']) }}">
                                            <i class="fas fa-list-check nav-icon"></i><p>Sync Status @if($syncPendingCount)<span class="right badge bg-warning text-dark">{{ $syncPendingCount }}</span>@endif</p>
                                        </a>
                                    </li>
                                @endcan
                                @can('view reports')
                                    <li class="nav-item">
                                        <a href="{{ route('sync.conflicts') }}" class="nav-link {{ $activeClass(['sync.conflicts', 'sync.conflicts.*']) }}">
                                            <i class="fas fa-exclamation-triangle nav-icon"></i><p>Conflicts @if($syncConflictCount)<span class="right badge bg-danger">{{ $syncConflictCount }}</span>@endif</p>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @can('manage inventory')
                        <li class="nav-item has-treeview {{ $treeClass(['products.*', 'categories.*', 'inventory.*', 'stock-movements.*']) }}">
                            <a href="#" class="nav-link {{ $treeLinkClass(['products.*', 'categories.*', 'inventory.*', 'stock-movements.*']) }}" role="button">
                                <i class="nav-icon fas fa-boxes"></i><p>Inventory <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('products.index') }}" class="nav-link {{ $activeClass(['products.*']) }}" data-offline-unsupported><i class="fas fa-box nav-icon"></i><p>Products</p></a></li>
                                <li class="nav-item"><a href="{{ route('categories.index') }}" class="nav-link {{ $activeClass(['categories.*']) }}"><i class="fas fa-tags nav-icon"></i><p>Categories</p></a></li>
                                <li class="nav-item"><a href="{{ route('inventory.index') }}" class="nav-link {{ $activeClass(['inventory.*']) }}" data-offline-unsupported><i class="fas fa-warehouse nav-icon"></i><p>Stock</p></a></li>
                                <li class="nav-item"><a href="{{ route('stock-movements.index') }}" class="nav-link {{ $activeClass(['stock-movements.*']) }}" data-offline-unsupported><i class="fas fa-arrow-right-arrow-left nav-icon"></i><p>Movements</p></a></li>
                            </ul>
                        </li>
                    @endcan

                    @can('view reports')
                        <li class="nav-item has-treeview {{ $treeClass(['reports.*']) }}">
                            <a href="#" class="nav-link {{ $treeLinkClass(['reports.*']) }}" role="button">
                                <i class="nav-icon fas fa-chart-line"></i><p>Reports <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('reports.daily-sales') }}" class="nav-link {{ $activeClass(['reports.daily-sales']) }}" data-offline-unsupported><i class="fas fa-calendar-day nav-icon"></i><p>Daily Sales</p></a></li>
                                <li class="nav-item"><a href="{{ route('reports.vat-sales') }}" class="nav-link {{ $activeClass(['reports.vat-sales']) }}" data-offline-unsupported><i class="fas fa-percent nav-icon"></i><p>VAT Sales</p></a></li>
                                <li class="nav-item"><a href="{{ route('reports.non-vat-sales') }}" class="nav-link {{ $activeClass(['reports.non-vat-sales']) }}" data-offline-unsupported><i class="fas fa-file-circle-xmark nav-icon"></i><p>Non-VAT Sales</p></a></li>
                                <li class="nav-item"><a href="{{ route('reports.discounts') }}" class="nav-link {{ $activeClass(['reports.discounts']) }}" data-offline-unsupported><i class="fas fa-ticket nav-icon"></i><p>Discounts</p></a></li>
                                <li class="nav-item"><a href="{{ route('reports.voids') }}" class="nav-link {{ $activeClass(['reports.voids']) }}" data-offline-unsupported><i class="fas fa-ban nav-icon"></i><p>Voids</p></a></li>
                                <li class="nav-item"><a href="{{ route('reports.refunds') }}" class="nav-link {{ $activeClass(['reports.refunds']) }}" data-offline-unsupported><i class="fas fa-rotate-left nav-icon"></i><p>Refunds</p></a></li>
                                <li class="nav-item"><a href="{{ route('reports.audit-trail') }}" class="nav-link {{ $activeClass(['reports.audit-trail']) }}" data-offline-unsupported><i class="fas fa-shield-halved nav-icon"></i><p>Audit Trail</p></a></li>
                            </ul>
                        </li>
                    @endcan

                    @if ($canSetupNav)
                        <li class="nav-item has-treeview {{ $treeClass(['tenants.*', 'branches.*', 'terminals.*', 'settings.bir.*', 'settings.invoice.*', 'invoice-preview.*', 'compliance.*', 'onboarding.*']) }}">
                            <a href="#" class="nav-link {{ $treeLinkClass(['tenants.*', 'branches.*', 'terminals.*', 'settings.bir.*', 'settings.invoice.*', 'invoice-preview.*', 'compliance.*', 'onboarding.*']) }}" role="button">
                                <i class="nav-icon fas fa-clipboard-check"></i><p>Setup / Compliance <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('manage tenants')
                                    <li class="nav-item"><a href="{{ route('tenants.index') }}" class="nav-link {{ $activeClass(['tenants.index', 'tenants.create', 'tenants.edit']) }}"><i class="fas fa-building nav-icon"></i><p>Tenants</p></a></li>
                                @endcan
                                @can('manage branches')
                                    <li class="nav-item"><a href="{{ route('branches.index') }}" class="nav-link {{ $activeClass(['branches.*']) }}"><i class="fas fa-code-branch nav-icon"></i><p>Branches</p></a></li>
                                @endcan
                                @can('manage terminals')
                                    <li class="nav-item"><a href="{{ route('terminals.index') }}" class="nav-link {{ $activeClass(['terminals.*']) }}"><i class="fas fa-desktop nav-icon"></i><p>Terminals</p></a></li>
                                @endcan
                                @can('manage settings')
                                    <li class="nav-item"><a href="{{ route('settings.bir.edit') }}" class="nav-link {{ $activeClass(['settings.bir.*']) }}"><i class="fas fa-certificate nav-icon"></i><p>BIR Info</p></a></li>
                                    <li class="nav-item"><a href="{{ route('settings.invoice.edit') }}" class="nav-link {{ $activeClass(['settings.invoice.*']) }}"><i class="fas fa-file-invoice nav-icon"></i><p>Invoice Settings</p></a></li>
                                    <li class="nav-item"><a href="{{ route('invoice-preview.show') }}" class="nav-link {{ $activeClass(['invoice-preview.*']) }}"><i class="fas fa-eye nav-icon"></i><p>Invoice Preview</p></a></li>
                                    <li class="nav-item"><a href="{{ route('onboarding.index') }}" class="nav-link {{ $activeClass(['onboarding.*']) }}"><i class="fas fa-list-check nav-icon"></i><p>Onboarding</p></a></li>
                                @endcan
                                @can('view compliance')
                                    <li class="nav-item"><a href="{{ route('compliance.checklist') }}" class="nav-link {{ $activeClass(['compliance.*']) }}"><i class="fas fa-clipboard-list nav-icon"></i><p>Compliance Checklist</p></a></li>
                                @endcan
                            </ul>
                        </li>
                    @endif

                    @if ($canAdminNav)
                        <li class="nav-item has-treeview {{ $treeClass(['settings.edit', 'tenants.license.*']) }}">
                            <a href="#" class="nav-link {{ $treeLinkClass(['settings.edit', 'tenants.license.*']) }}" role="button">
                                <i class="nav-icon fas fa-users-cog"></i><p>Administration <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                @if ($user?->tenant_id && $user?->can('manage licenses'))
                                    <li class="nav-item"><a href="{{ route('tenants.license.edit', $user->tenant_id) }}" class="nav-link {{ $activeClass(['tenants.license.*']) }}"><i class="fas fa-key nav-icon"></i><p>Licensing</p></a></li>
                                @endif
                                @can('manage settings')
                                    <li class="nav-item"><a href="{{ route('settings.edit') }}" class="nav-link {{ $activeClass(['settings.edit']) }}" data-offline-unsupported><i class="fas fa-cogs nav-icon"></i><p>Settings</p></a></li>
                                @endcan
                            </ul>
                        </li>
                    @endif
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

document.addEventListener('DOMContentLoaded', () => {
    const treeParents = document.querySelectorAll('.nav-sidebar .nav-item > .nav-link[href="#"]');

    document.querySelectorAll('.nav-sidebar .nav-treeview').forEach(tree => {
        if (tree.parentElement?.classList.contains('menu-open')) {
            tree.style.display = 'block';
        } else {
            tree.style.display = 'none';
        }
    });

    treeParents.forEach(link => {
        link.addEventListener('click', event => {
            const parent = link.closest('.nav-item');
            const tree = parent?.querySelector(':scope > .nav-treeview');
            if (! tree) return;

            event.preventDefault();
            event.stopImmediatePropagation();

            const isOpen = parent.classList.toggle('menu-open');
            if (window.jQuery) {
                window.jQuery(tree).stop(true, true)[isOpen ? 'slideDown' : 'slideUp'](180);
            } else {
                tree.style.display = isOpen ? 'block' : 'none';
            }
        });
    });
});
</script>
@stack('scripts')
</body>
</html>
