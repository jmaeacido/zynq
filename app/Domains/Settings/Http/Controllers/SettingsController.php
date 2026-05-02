<?php

namespace App\Domains\Settings\Http\Controllers;

use App\Domains\Settings\Http\Requests\UpdateTenantSettingsRequest;
use App\Domains\Settings\Services\TenantSettingsService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request, TenantContext $context): View
    {
        $tenants = $context->scopeForUser(Tenant::with('settings'), $request->user())->orderBy('business_name')->get();
        $tenant = $tenants->first();

        return view('settings.edit', [
            'tenants' => $tenants,
            'tenant' => $tenant,
            'settings' => $tenant?->settings->keyBy('key') ?? collect(),
        ]);
    }

    public function update(UpdateTenantSettingsRequest $request, TenantSettingsService $service): RedirectResponse
    {
        $tenant = Tenant::findOrFail($request->integer('tenant_id'));
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $tenant->id !== (int) $user->tenant_id) {
            abort(403, 'Settings are outside your tenant.');
        }

        $service->set($tenant, 'vat_rate', ['rate' => (float) $request->input('vat_rate')]);
        $service->set($tenant, 'invoice_title_default', ['title' => $request->input('invoice_title_default')]);
        $service->set($tenant, 'compliance_contact_email', ['email' => $request->input('compliance_contact_email')]);

        return back()->with('status', 'Settings updated.');
    }
}
