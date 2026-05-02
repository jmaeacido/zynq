<?php

namespace App\Domains\Settings\Http\Controllers;

use App\Domains\Settings\Http\Requests\UpdateInvoiceSettingsRequest;
use App\Domains\Settings\Services\TenantSetupService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceSettingsController extends Controller
{
    public function edit(Request $request, TenantContext $context): View
    {
        $tenants = $context->scopeForUser(Tenant::query(), $request->user())->orderBy('business_name')->get();
        $tenant = $request->integer('tenant_id')
            ? $tenants->firstWhere('id', $request->integer('tenant_id'))
            : $tenants->first();

        return view('settings.invoice', [
            'tenants' => $tenants,
            'tenant' => $tenant,
        ]);
    }

    public function update(UpdateInvoiceSettingsRequest $request, TenantSetupService $setup): RedirectResponse
    {
        $tenant = Tenant::findOrFail($request->integer('tenant_id'));
        $this->authorizeTenant($request, $tenant);
        $setup->updateInvoiceSettings($tenant, $request->validated(), $request->user(), $request->file('logo'));

        return back()->with('status', 'Invoice branding settings updated.');
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $tenant->id !== (int) $user->tenant_id) {
            abort(403, 'Invoice setup is outside your tenant.');
        }
    }
}
