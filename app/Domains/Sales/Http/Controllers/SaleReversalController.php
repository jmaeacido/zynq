<?php

namespace App\Domains\Sales\Http\Controllers;

use App\Domains\Sales\Http\Requests\StoreSaleReversalRequest;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Services\SaleReversalService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SaleReversalController extends Controller
{
    public function create(Request $request, Sale $sale): View
    {
        $this->authorizeTenant($request, $sale);

        return view('sales.reversal', ['sale' => $sale->load(['items', 'reversals'])]);
    }

    public function store(StoreSaleReversalRequest $request, Sale $sale, SaleReversalService $service): RedirectResponse
    {
        $this->authorizeTenant($request, $sale);

        try {
            if ($request->input('type') === 'void') {
                $service->void($sale, $request->user(), $request->input('reason'), $request->boolean('return_to_stock'));
            } else {
                $service->refund($sale, $request->user(), $request->input('reason'), $request->boolean('return_to_stock'), $request->input('items'));
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['reversal' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('sales.show', $sale)->with('status', 'Reversal recorded.');
    }

    private function authorizeTenant(Request $request, Sale $sale): void
    {
        $user = $request->user();
        if (! $user->hasRole('Super Admin') && (int) $sale->tenant_id !== (int) $user->tenant_id) {
            abort(403);
        }
    }
}
