<?php

namespace App\Domains\Inventory\Http\Controllers;

use App\Domains\Inventory\Http\Requests\UpdateInventoryStockRequest;
use App\Domains\Inventory\Models\InventoryStock;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $stocks = $context->scopeForUser(
            InventoryStock::with(['tenant', 'branch', 'product'])->orderBy('tenant_id')->orderBy('branch_id'),
            $request->user()
        )->paginate(20);

        return view('inventory.index', ['stocks' => $stocks]);
    }

    public function edit(Request $request, InventoryStock $inventory): View
    {
        $this->authorizeTenant($request, $inventory);

        return view('inventory.form', ['stock' => $inventory->load(['tenant', 'branch', 'product'])]);
    }

    public function update(UpdateInventoryStockRequest $request, InventoryStock $inventory, InventoryService $service): RedirectResponse
    {
        $service->setReorderPoint($inventory, (float) $request->input('reorder_point'));

        return redirect()->route('inventory.index')->with('status', 'Reorder point updated.');
    }

    private function authorizeTenant(Request $request, InventoryStock $stock): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $stock->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Inventory record does not belong to your tenant.');
        }
    }
}
