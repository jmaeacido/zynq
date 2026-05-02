<?php

namespace App\Http\Controllers;

use App\Domains\Branches\Models\Branch;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Domains\Terminals\Models\Terminal;
use App\Domains\Terminals\Services\TerminalComplianceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TenantContext $tenantContext, TerminalComplianceService $compliance): View
    {
        $tenant = $tenantContext->forUser($request->user());
        $tenantQuery = Tenant::query();
        $branchQuery = Branch::query();
        $terminalQuery = Terminal::query()->with(['tenant', 'branch']);

        if ($tenant) {
            $branchQuery->where('tenant_id', $tenant->id);
            $terminalQuery->where('tenant_id', $tenant->id);
        }

        $terminals = $terminalQuery->latest()->limit(8)->get();

        return view('dashboard.index', [
            'tenant' => $tenant,
            'tenantCount' => $request->user()->hasRole('Super Admin') ? $tenantQuery->count() : 1,
            'branchCount' => $branchQuery->count(),
            'terminalCount' => $terminalQuery->count(),
            'terminals' => $terminals,
            'compliance' => $compliance,
        ]);
    }
}
