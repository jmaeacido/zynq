<?php

namespace App\Domains\Settings\Http\Controllers;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Branches\Models\Branch;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Domains\Terminals\Models\Terminal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoicePreviewController extends Controller
{
    public function __invoke(Request $request, TenantContext $context, AuditService $audit): View
    {
        $tenants = $context->scopeForUser(Tenant::with(['branches', 'terminals.branch']), $request->user())->orderBy('business_name')->get();
        $tenant = $request->integer('tenant_id') ? $tenants->firstWhere('id', $request->integer('tenant_id')) : $tenants->first();
        $branches = $tenant ? Branch::where('tenant_id', $tenant->id)->orderBy('branch_name')->get() : collect();
        $terminals = $tenant ? Terminal::where('tenant_id', $tenant->id)->with('branch')->orderBy('terminal_name')->get() : collect();
        $branch = $request->integer('branch_id') ? $branches->firstWhere('id', $request->integer('branch_id')) : $branches->first();
        $terminal = $request->integer('terminal_id') ? $terminals->firstWhere('id', $request->integer('terminal_id')) : $terminals->firstWhere('branch_id', $branch?->id) ?? $terminals->first();
        $template = in_array($request->query('template'), ['thermal', 'a4'], true) ? $request->query('template') : 'thermal';

        if ($tenant) {
            $audit->record($request->user(), 'previewed', 'invoice_preview', Tenant::class, $tenant->id, [
                'branch_id' => $branch?->id,
                'terminal_id' => $terminal?->id,
                'template' => $template,
            ], tenantId: $tenant->id, branchId: $branch?->id);
        }

        return view('invoice_preview.show', [
            'tenants' => $tenants,
            'tenant' => $tenant,
            'branches' => $branches,
            'branch' => $branch,
            'terminals' => $terminals,
            'terminal' => $terminal,
            'template' => $template,
            'items' => [
                ['name' => 'Sample VATable Item', 'quantity' => 2, 'unit_price' => 100.00, 'total' => 200.00],
                ['name' => 'Sample VAT-Exempt Item', 'quantity' => 1, 'unit_price' => 50.00, 'total' => 50.00],
            ],
        ]);
    }
}
