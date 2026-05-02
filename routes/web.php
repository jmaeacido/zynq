<?php

use App\Domains\Branches\Http\Controllers\BranchController;
use App\Domains\Inventory\Http\Controllers\InventoryController;
use App\Domains\Inventory\Http\Controllers\StockMovementController;
use App\Domains\Licensing\Http\Controllers\LicenseController;
use App\Domains\Products\Http\Controllers\ProductCategoryController;
use App\Domains\Products\Http\Controllers\ProductController;
use App\Domains\Reports\Http\Controllers\CashReadingController;
use App\Domains\Reports\Http\Controllers\DiscountReportController;
use App\Domains\Reports\Http\Controllers\PlaceholderReportController;
use App\Domains\Reports\Http\Controllers\ReversalReportController;
use App\Domains\Reports\Http\Controllers\TaxReportController;
use App\Domains\Sales\Http\Controllers\CashSessionController;
use App\Domains\Sales\Http\Controllers\InvoiceController;
use App\Domains\Sales\Http\Controllers\OfflineSyncController;
use App\Domains\Sales\Http\Controllers\PosController;
use App\Domains\Sales\Http\Controllers\SaleController;
use App\Domains\Sales\Http\Controllers\SaleReversalController;
use App\Domains\Settings\Http\Controllers\BirInfoController;
use App\Domains\Settings\Http\Controllers\ComplianceController;
use App\Domains\Settings\Http\Controllers\InvoicePreviewController;
use App\Domains\Settings\Http\Controllers\InvoiceSettingsController;
use App\Domains\Settings\Http\Controllers\OnboardingController;
use App\Domains\Settings\Http\Controllers\SettingsController;
use App\Domains\Tenancy\Http\Controllers\TenantController;
use App\Domains\Terminals\Http\Controllers\TerminalController;
use App\Domains\Users\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'tenant.active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('tenants', TenantController::class)->except(['show', 'destroy'])->middleware('permission:manage tenants');
    Route::get('tenants/{tenant}/license', [LicenseController::class, 'edit'])->middleware('permission:manage licenses')->name('tenants.license.edit');
    Route::put('tenants/{tenant}/license', [LicenseController::class, 'update'])->middleware('permission:manage licenses')->name('tenants.license.update');
    Route::resource('branches', BranchController::class)->except(['show', 'destroy'])->middleware(['permission:manage branches', 'branch.access']);
    Route::resource('terminals', TerminalController::class)->except(['show', 'destroy'])->middleware('permission:manage terminals');
    Route::resource('categories', ProductCategoryController::class)->parameters(['categories' => 'category'])->except(['show', 'destroy'])->middleware('permission:manage inventory');
    Route::resource('products', ProductController::class)->except(['show', 'destroy'])->middleware('permission:manage inventory');
    Route::patch('products/{product}/archive', [ProductController::class, 'archive'])->middleware('permission:manage inventory')->name('products.archive');
    Route::resource('inventory', InventoryController::class)->only(['index', 'edit', 'update'])->middleware('permission:manage inventory');
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'create', 'store'])->middleware('permission:manage inventory');
    Route::get('pos', [PosController::class, 'index'])->middleware('permission:create sales')->name('pos.checkout');
    Route::get('pos/products', [PosController::class, 'products'])->middleware('permission:create sales')->name('pos.products');
    Route::post('pos/sales', [PosController::class, 'store'])->middleware('permission:create sales')->name('pos.sales.store');
    Route::get('sync/offline-snapshot', [OfflineSyncController::class, 'snapshot'])->middleware('permission:create sales')->name('sync.snapshot');
    Route::post('sync/offline-sales', [OfflineSyncController::class, 'store'])->middleware('permission:create sales')->name('sync.offline-sales.store');
    Route::get('sync/status', [OfflineSyncController::class, 'status'])->middleware('permission:create sales')->name('sync.status');
    Route::get('sync/conflicts', [OfflineSyncController::class, 'conflicts'])->middleware('permission:create sales')->name('sync.conflicts');
    Route::resource('cash-sessions', CashSessionController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:create sales');
    Route::post('cash-sessions/{cash_session}/close', [CashSessionController::class, 'close'])->middleware('permission:create sales')->name('cash-sessions.close');
    Route::resource('sales', SaleController::class)->only(['index', 'show'])->middleware('role_or_permission:Super Admin|view reports|create sales');
    Route::get('sales/{sale}/reversal', [SaleReversalController::class, 'create'])->middleware('role_or_permission:Super Admin|approve voids|approve refunds')->name('sales.reversals.create');
    Route::post('sales/{sale}/reversal', [SaleReversalController::class, 'store'])->middleware('role_or_permission:Super Admin|approve voids|approve refunds')->name('sales.reversals.store');
    Route::get('sales/{sale}/invoice/thermal', [InvoiceController::class, 'thermal'])->middleware('role_or_permission:Super Admin|view reports|create sales')->name('sales.invoice.thermal');
    Route::get('sales/{sale}/invoice/a4', [InvoiceController::class, 'a4'])->middleware('role_or_permission:Super Admin|view reports|create sales')->name('sales.invoice.a4');
    Route::get('readings/x/{cash_session}', [CashReadingController::class, 'x'])->middleware('role_or_permission:Super Admin|view reports|create sales')->name('readings.x');
    Route::get('readings/z/{cash_session}', [CashReadingController::class, 'z'])->middleware('role_or_permission:Super Admin|view reports|create sales')->name('readings.z');
    Route::get('readings/cashier/{cash_session}', [CashReadingController::class, 'cashier'])->middleware('role_or_permission:Super Admin|view reports|create sales')->name('readings.cashier');
    Route::get('readings/terminal/{cash_session}', [CashReadingController::class, 'terminal'])->middleware('role_or_permission:Super Admin|view reports|create sales')->name('readings.terminal');
    Route::get('reports/daily-sales', [CashReadingController::class, 'daily'])->middleware('permission:view reports')->name('reports.daily-sales');
    Route::get('reports/vat-sales', [TaxReportController::class, 'vat'])->middleware('permission:view reports')->name('reports.vat-sales');
    Route::get('reports/non-vat-sales', [TaxReportController::class, 'nonVat'])->middleware('permission:view reports')->name('reports.non-vat-sales');
    Route::get('reports/discounts', DiscountReportController::class)->middleware('permission:view reports')->name('reports.discounts');
    Route::get('reports/voids', [ReversalReportController::class, 'voids'])->middleware('permission:view reports')->name('reports.voids');
    Route::get('reports/refunds', [ReversalReportController::class, 'refunds'])->middleware('permission:view reports')->name('reports.refunds');
    Route::get('reports/audit-trail', [ReversalReportController::class, 'audit'])->middleware('permission:view reports')->name('reports.audit-trail');
    Route::get('reports/placeholders/{type}', PlaceholderReportController::class)->middleware('permission:view reports')->name('reports.placeholders');
    Route::get('/settings', [SettingsController::class, 'edit'])->middleware('permission:manage settings')->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('permission:manage settings')->name('settings.update');
    Route::get('/onboarding', [OnboardingController::class, 'index'])->middleware('permission:manage settings')->name('onboarding.index');
    Route::get('/onboarding/branches', [OnboardingController::class, 'branches'])->middleware('permission:manage branches')->name('onboarding.branches');
    Route::get('/onboarding/terminals', [OnboardingController::class, 'terminals'])->middleware('permission:manage terminals')->name('onboarding.terminals');
    Route::post('/onboarding/{tenant}/complete', [OnboardingController::class, 'complete'])->middleware('permission:manage settings')->name('onboarding.complete');
    Route::get('/settings/bir', [BirInfoController::class, 'edit'])->middleware('permission:manage settings')->name('settings.bir.edit');
    Route::put('/settings/bir', [BirInfoController::class, 'update'])->middleware('permission:manage settings')->name('settings.bir.update');
    Route::get('/settings/invoice', [InvoiceSettingsController::class, 'edit'])->middleware('permission:manage settings')->name('settings.invoice.edit');
    Route::put('/settings/invoice', [InvoiceSettingsController::class, 'update'])->middleware('permission:manage settings')->name('settings.invoice.update');
    Route::get('/invoice-preview', InvoicePreviewController::class)->middleware('permission:manage settings')->name('invoice-preview.show');
    Route::get('/compliance/checklist', ComplianceController::class)->middleware('permission:view compliance')->name('compliance.checklist');
});
