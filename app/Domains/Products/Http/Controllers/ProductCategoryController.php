<?php

namespace App\Domains\Products\Http\Controllers;

use App\Domains\Products\Http\Requests\StoreProductCategoryRequest;
use App\Domains\Products\Http\Requests\UpdateProductCategoryRequest;
use App\Domains\Products\Models\ProductCategory;
use App\Domains\Products\Services\ProductCategoryService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(Request $request, TenantContext $context): View
    {
        $categories = $context->scopeForUser(ProductCategory::with('tenant')->latest(), $request->user())->paginate(20);

        return view('categories.index', ['categories' => $categories]);
    }

    public function create(Request $request, TenantContext $context): View
    {
        return view('categories.form', [
            'category' => new ProductCategory(),
            'tenants' => $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get(),
        ]);
    }

    public function store(StoreProductCategoryRequest $request, ProductCategoryService $service): RedirectResponse
    {
        $category = $service->create($request->safe()->merge(['active' => $request->boolean('active', true)])->all());

        return redirect()->route('categories.edit', $category)->with('status', 'Product category saved.');
    }

    public function edit(Request $request, ProductCategory $category, TenantContext $context): View
    {
        $this->authorizeTenant($request, $category);

        return view('categories.form', [
            'category' => $category,
            'tenants' => $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get(),
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category, ProductCategoryService $service): RedirectResponse
    {
        $service->update($category, $request->safe()->merge(['active' => $request->boolean('active')])->all());

        return back()->with('status', 'Product category updated.');
    }

    private function authorizeTenant(Request $request, ProductCategory $category): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $category->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Category does not belong to your tenant.');
        }
    }
}
