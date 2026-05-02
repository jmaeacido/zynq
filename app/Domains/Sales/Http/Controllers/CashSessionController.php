<?php

namespace App\Domains\Sales\Http\Controllers;

use App\Domains\Branches\Models\Branch;
use App\Domains\Reports\Services\CashReadingReportService;
use App\Domains\Sales\Http\Requests\CloseCashSessionRequest;
use App\Domains\Sales\Http\Requests\OpenCashSessionRequest;
use App\Domains\Sales\Models\CashSession;
use App\Domains\Sales\Services\CashSessionService;
use App\Domains\Tenancy\Services\TenantContext;
use App\Domains\Terminals\Models\Terminal;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CashSessionController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $sessions = $context->scopeForUser(CashSession::with(['tenant', 'branch', 'terminal', 'user'])->latest('opened_at'), $request->user())->paginate(20);

        return view('cash_sessions.index', compact('sessions'));
    }

    public function create(Request $request, TenantContext $context): View
    {
        return view('cash_sessions.create', [
            'branches' => $context->scopeForUser(Branch::where('status', 'active'), $request->user())->orderBy('branch_name')->get(),
            'terminals' => $context->scopeForUser(Terminal::where('active', true), $request->user())->orderBy('terminal_name')->get(),
        ]);
    }

    public function store(OpenCashSessionRequest $request, CashSessionService $service): RedirectResponse
    {
        try {
            $session = $service->open(
                Branch::findOrFail($request->integer('branch_id')),
                Terminal::findOrFail($request->integer('terminal_id')),
                $request->user(),
                (float) $request->input('opening_cash'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['cash_session' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('cash-sessions.show', $session)->with('status', 'Cash session opened.');
    }

    public function show(Request $request, CashSession $cashSession, CashReadingReportService $reports): View
    {
        $this->authorizeTenant($request, $cashSession);

        return view('cash_sessions.show', ['session' => $cashSession->load(['tenant', 'branch', 'terminal', 'user']), 'reading' => $reports->reading($cashSession)]);
    }

    public function close(CloseCashSessionRequest $request, CashSession $cashSession, CashSessionService $service): RedirectResponse
    {
        try {
            $session = $service->close($cashSession, (float) $request->input('actual_cash'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['actual_cash' => $exception->getMessage()]);
        }

        return redirect()->route('cash-sessions.show', $session)->with('status', 'Cash session closed.');
    }

    private function authorizeTenant(Request $request, CashSession $cashSession): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $cashSession->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Cash session does not belong to your tenant.');
        }
    }
}
