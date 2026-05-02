<?php

namespace App\Domains\Sales\Http\Controllers;

use App\Domains\Sales\Models\Sale;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function thermal(Request $request, Sale $sale): View
    {
        $this->authorizeTenant($request, $sale);

        return view('invoices.thermal', ['sale' => $sale->load(['tenant', 'branch', 'terminal', 'cashier', 'items', 'payments', 'discounts', 'taxSummary'])]);
    }

    public function a4(Request $request, Sale $sale): View
    {
        $this->authorizeTenant($request, $sale);

        return view('invoices.a4', ['sale' => $sale->load(['tenant', 'branch', 'terminal', 'cashier', 'items', 'payments', 'discounts', 'taxSummary'])]);
    }

    private function authorizeTenant(Request $request, Sale $sale): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $sale->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Invoice does not belong to your tenant.');
        }
    }
}
