<?php

namespace App\Domains\Terminals\Http\Controllers;

use App\Domains\Branches\Models\Branch;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Domains\Terminals\Http\Requests\StoreTerminalRequest;
use App\Domains\Terminals\Http\Requests\UpdateTerminalRequest;
use App\Domains\Terminals\Models\Terminal;
use App\Domains\Terminals\Services\TerminalComplianceService;
use App\Domains\Terminals\Services\TerminalService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TerminalController extends Controller
{
    public function index(Request $request, TenantContext $context, TerminalComplianceService $compliance): View
    {
        $terminals = $context->scopeForUser(Terminal::with(['tenant', 'branch'])->latest(), $request->user())->paginate(20);

        return view('terminals.index', ['terminals' => $terminals, 'compliance' => $compliance]);
    }

    public function create(Request $request, TenantContext $context): View
    {
        return view('terminals.form', [
            'terminal' => new Terminal(),
            'tenants' => $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get(),
            'branches' => $context->scopeForUser(Branch::query(), $request->user())->orderBy('branch_name')->get(),
        ]);
    }

    public function store(StoreTerminalRequest $request, TerminalService $service): RedirectResponse
    {
        $terminal = $service->create($request->safe()->merge(['active' => $request->boolean('active', true)])->all());

        return redirect()->route('terminals.edit', $terminal)->with('status', 'Terminal created.');
    }

    public function edit(Request $request, Terminal $terminal, TenantContext $context, TerminalComplianceService $compliance): View
    {
        $this->authorizeTerminalTenant($request, $terminal);

        return view('terminals.form', [
            'terminal' => $terminal,
            'tenants' => $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get(),
            'branches' => $context->scopeForUser(Branch::query(), $request->user())->orderBy('branch_name')->get(),
            'missing' => $compliance->missingRequirements($terminal),
        ]);
    }

    public function update(UpdateTerminalRequest $request, Terminal $terminal, TerminalService $service): RedirectResponse
    {
        $this->authorizeTerminalTenant($request, $terminal);

        $service->update($terminal, $request->safe()->merge(['active' => $request->boolean('active')])->all());

        return back()->with('status', 'Terminal updated.');
    }

    private function authorizeTerminalTenant(Request $request, Terminal $terminal): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $terminal->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Terminal does not belong to your tenant.');
        }

        if (! $user->hasRole('Super Admin') && $user->branch_id && (int) $terminal->branch_id !== (int) $user->branch_id) {
            abort(403, 'You do not have access to this terminal branch.');
        }
    }
}
