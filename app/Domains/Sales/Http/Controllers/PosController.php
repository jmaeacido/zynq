<?php

namespace App\Domains\Sales\Http\Controllers;

use App\Domains\Branches\Models\Branch;
use App\Domains\Products\Models\Product;
use App\Domains\Sales\Http\Requests\StoreSaleRequest;
use App\Domains\Sales\Services\SaleService;
use App\Domains\Tenancy\Services\TenantContext;
use App\Domains\Terminals\Models\Terminal;
use App\Domains\Terminals\Services\TerminalComplianceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Js;
use Illuminate\View\View;
use InvalidArgumentException;

class PosController extends Controller
{
    public function index(Request $request, TenantContext $context, TerminalComplianceService $compliance): View
    {
        $branches = $context->scopeForUser(Branch::query()->where('status', 'active'), $request->user())->orderBy('branch_name')->get();
        $terminals = $context->scopeForUser(Terminal::query()->where('active', true), $request->user())->orderBy('terminal_name')->get();
        $products = $context->scopeForUser(Product::query()->where('active', true), $request->user())->orderBy('name')->limit(100)->get();
        $productPayload = $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'price' => (float) $product->selling_price,
        ])->values();

        return view('pos.checkout', compact('branches', 'terminals', 'products', 'productPayload', 'compliance'));
    }

    public function products(Request $request, TenantContext $context): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $products = $context->scopeForUser(Product::query()->where('active', true), $request->user())
            ->when($query !== '', function ($builder) use ($query): void {
                $builder->where(function ($nested) use ($query): void {
                    $nested->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'sku', 'barcode', 'name', 'unit', 'selling_price', 'tax_type']);

        return response()->json($products);
    }

    public function store(StoreSaleRequest $request, SaleService $service): RedirectResponse
    {
        try {
            $sale = $service->create($request->validated(), $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['sale' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('sales.show', $sale)->with('status', 'Sale completed.');
    }
}
