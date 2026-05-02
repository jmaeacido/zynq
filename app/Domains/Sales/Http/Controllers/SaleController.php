<?php

namespace App\Domains\Sales\Http\Controllers;

use App\Domains\Sales\Models\Sale;
use App\Domains\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $sales = $context->scopeForUser(Sale::with(['tenant', 'branch', 'terminal', 'cashier'])->latest(), $request->user())->paginate(20);

        return view('sales.index', ['sales' => $sales]);
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorizeTenant($request, $sale);

        return view('sales.show', ['sale' => $sale->load(['tenant', 'branch', 'terminal', 'cashier', 'items', 'payments', 'discounts', 'taxSummary', 'reversals'])]);
    }

    private function authorizeTenant(Request $request, Sale $sale): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $sale->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Sale does not belong to your tenant.');
        }
    }
}
