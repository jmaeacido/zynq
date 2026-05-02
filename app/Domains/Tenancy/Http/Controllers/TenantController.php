<?php

namespace App\Domains\Tenancy\Http\Controllers;

use App\Domains\Tenancy\Http\Requests\StoreTenantRequest;
use App\Domains\Tenancy\Http\Requests\UpdateTenantRequest;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        return view('tenants.index', ['tenants' => Tenant::latest()->paginate(20)]);
    }

    public function create(): View
    {
        return view('tenants.form', ['tenant' => new Tenant()]);
    }

    public function store(StoreTenantRequest $request, TenantService $service): RedirectResponse
    {
        $tenant = $service->create($request->safe()->merge(['active' => $request->boolean('active', true)])->all());

        return redirect()->route('tenants.edit', $tenant)->with('status', 'Tenant created.');
    }

    public function edit(Tenant $tenant): View
    {
        return view('tenants.form', ['tenant' => $tenant]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant, TenantService $service): RedirectResponse
    {
        $service->update($tenant, $request->safe()->merge(['active' => $request->boolean('active')])->all());

        return back()->with('status', 'Tenant updated.');
    }
}
