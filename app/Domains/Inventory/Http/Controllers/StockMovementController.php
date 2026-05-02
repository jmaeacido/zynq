<?php

namespace App\Domains\Inventory\Http\Controllers;

use App\Domains\Branches\Models\Branch;
use App\Domains\Inventory\Http\Requests\StoreStockMovementRequest;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Products\Models\Product;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class StockMovementController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $movements = $context->scopeForUser(
            StockMovement::with(['tenant', 'branch', 'product', 'user'])->latest(),
            $request->user()
        )->paginate(30);

        return view('stock_movements.index', ['movements' => $movements]);
    }

    public function create(Request $request, TenantContext $context): View
    {
        return view('stock_movements.form', [
            'tenants' => $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get(),
            'branches' => $context->scopeForUser(Branch::query(), $request->user())->orderBy('branch_name')->get(),
            'products' => $context->scopeForUser(Product::query()->where('active', true), $request->user())->orderBy('name')->get(),
        ]);
    }

    public function store(StoreStockMovementRequest $request, InventoryService $service): RedirectResponse
    {
        $product = Product::findOrFail($request->integer('product_id'));
        $branch = Branch::findOrFail($request->integer('branch_id'));

        try {
            DB::transaction(function () use ($request, $service, $product, $branch): void {
                match ($request->input('movement_type')) {
                    'stock_in' => $service->stockIn($product, $branch, (float) $request->input('quantity'), $request->input('reason'), $request->user(), $request->input('reference_number')),
                    'stock_out' => $service->stockOut($product, $branch, (float) $request->input('quantity'), $request->input('reason'), $request->user(), $request->input('reference_number')),
                    'adjustment' => $service->adjust($product, $branch, (float) $request->input('target_quantity'), $request->input('reason'), $request->user(), $request->input('reference_number')),
                };
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('stock-movements.index')->with('status', 'Stock movement recorded.');
    }
}
