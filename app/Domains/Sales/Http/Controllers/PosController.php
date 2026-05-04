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
            'unit' => $product->unit,
            'price' => (float) $product->selling_price,
            'tax_type' => $product->tax_type,
        ])->values();

        return view('pos.checkout', compact('branches', 'terminals', 'products', 'productPayload', 'compliance'));
    }

    public function products(Request $request, TenantContext $context): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $compactQuery = preg_replace('/[^A-Za-z0-9]/', '', $query);
        $products = $context->scopeForUser(Product::query()->where('active', true), $request->user())
            ->when($query !== '', function ($builder) use ($query, $compactQuery): void {
                $builder->where(function ($nested) use ($query, $compactQuery): void {
                    $nested->where('barcode', $query)
                        ->orWhere('sku', $query)
                        ->orWhereRaw("REPLACE(REPLACE(barcode, '-', ''), ' ', '') = ?", [$compactQuery])
                        ->orWhereRaw("REPLACE(REPLACE(sku, '-', ''), ' ', '') = ?", [$compactQuery])
                        ->orWhere('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%");
                });
            })
            ->when($query !== '', fn ($builder) => $builder->orderByRaw(
                "CASE WHEN barcode = ? THEN 0 WHEN sku = ? THEN 1 WHEN REPLACE(REPLACE(barcode, '-', ''), ' ', '') = ? THEN 2 WHEN REPLACE(REPLACE(sku, '-', ''), ' ', '') = ? THEN 3 ELSE 4 END",
                [$query, $query, $compactQuery, $compactQuery],
            ))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'sku', 'barcode', 'name', 'unit', 'selling_price', 'tax_type']);

        return response()->json($products);
    }

    public function store(StoreSaleRequest $request, SaleService $service): RedirectResponse|JsonResponse
    {
        try {
            $sale = $service->create($request->validated(), $request->user());
        } catch (InvalidArgumentException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['sale' => $exception->getMessage()])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sale completed.',
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'show_url' => route('sales.show', $sale),
                'thermal_invoice_url' => route('sales.invoice.thermal', $sale),
                'a4_invoice_url' => route('sales.invoice.a4', $sale),
            ]);
        }

        return redirect()->route('sales.show', $sale)->with('status', 'Sale completed.');
    }
}
