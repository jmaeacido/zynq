<?php

namespace App\Domains\Settings\Http\Controllers;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Tenancy\Services\TenantContext;
use App\Domains\Terminals\Models\Terminal;
use App\Domains\Terminals\Services\TerminalComplianceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplianceController extends Controller
{
    public function __invoke(Request $request, TenantContext $context, TerminalComplianceService $compliance, AuditService $audit): View
    {
        $terminals = $context->scopeForUser(Terminal::with(['tenant', 'branch'])->latest(), $request->user())->get();
        $audit->record($request->user(), 'viewed', 'compliance', Terminal::class, 'checklist', [
            'terminal_count' => $terminals->count(),
        ]);

        return view('compliance.index', ['terminals' => $terminals, 'compliance' => $compliance]);
    }
}
