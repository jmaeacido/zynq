<?php

namespace App\Domains\Settings\Http\Controllers;

use App\Domains\Settings\Http\Requests\UpdateBirInfoRequest;
use App\Domains\Settings\Services\TenantSetupService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BirInfoController extends Controller
{
    public function edit(Request $request, TenantContext $context): View
    {
        $tenants = $context->scopeForUser(Tenant::with('settings'), $request->user())->orderBy('business_name')->get();
        $tenant = $request->integer('tenant_id')
            ? $tenants->firstWhere('id', $request->integer('tenant_id'))
            : $tenants->first();

        return view('settings.bir', [
            'tenants' => $tenants,
            'tenant' => $tenant,
        ]);
    }

    public function update(UpdateBirInfoRequest $request, TenantSetupService $setup): RedirectResponse
    {
        $tenant = Tenant::findOrFail($request->integer('tenant_id'));
        $this->authorizeTenant($request, $tenant);
        $setup->updateBirInfo($tenant, $request->validated(), $request->user());

        return back()->with('status', 'BIR information updated.');
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $tenant->id !== (int) $user->tenant_id) {
            abort(403, 'BIR setup is outside your tenant.');
        }
    }
}
