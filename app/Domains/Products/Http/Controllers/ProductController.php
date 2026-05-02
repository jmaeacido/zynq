<?php

namespace App\Domains\Products\Http\Controllers;

use App\Domains\Products\Http\Requests\StoreProductRequest;
use App\Domains\Products\Http\Requests\UpdateProductRequest;
use App\Domains\Products\Models\Product;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Products\Services\ProductService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $products = $context->scopeForUser(Product::with(['tenant', 'category'])->latest(), $request->user())->paginate(20);

        return view('products.index', ['products' => $products]);
    }

    public function create(Request $request, TenantContext $context): View
    {
        return view('products.form', [
            'product' => new Product(['tax_type' => 'VATABLE', 'unit' => 'pcs']),
            'tenants' => $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get(),
            'categories' => $context->scopeForUser(ProductCategory::query(), $request->user())->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request, ProductService $service): RedirectResponse
    {
        $product = $service->create($request->safe()->merge(['active' => $request->boolean('active', true)])->all());

        return redirect()->route('products.edit', $product)->with('status', 'Product saved.');
    }

    public function edit(Request $request, Product $product, TenantContext $context): View
    {
        $this->authorizeTenant($request, $product);

        return view('products.form', [
            'product' => $product,
            'tenants' => $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get(),
            'categories' => $context->scopeForUser(ProductCategory::query(), $request->user())->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $service): RedirectResponse
    {
        $service->update($product, $request->safe()->merge(['active' => $request->boolean('active')])->all());

        return back()->with('status', 'Product updated.');
    }

    public function archive(Request $request, Product $product, ProductService $service): RedirectResponse
    {
        $this->authorizeTenant($request, $product);
        abort_unless($request->user()->can('manage inventory'), 403);

        $service->archive($product);

        return redirect()->route('products.index')->with('status', 'Product archived.');
    }

    private function authorizeTenant(Request $request, Product $product): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $product->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Product does not belong to your tenant.');
        }
    }
}
