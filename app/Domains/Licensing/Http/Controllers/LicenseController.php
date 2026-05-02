<?php

namespace App\Domains\Licensing\Http\Controllers;

use App\Domains\Licensing\Http\Requests\UpdateLicenseRequest;
use App\Domains\Licensing\Services\LicenseService;
use App\Domains\Tenancy\Models\Tenant;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function edit(Tenant $tenant, LicenseService $licenses): View
    {
        return view('licensing.edit', [
            'tenant' => $tenant,
            'licenseStatus' => $licenses->status($tenant),
            'statuses' => LicenseService::STATUSES,
        ]);
    }

    public function update(UpdateLicenseRequest $request, Tenant $tenant, LicenseService $licenses): RedirectResponse
    {
        $licenses->update($tenant, $request->safe()->merge(['active' => $request->boolean('active')])->all(), $request->user());

        return back()->with('status', 'License settings updated.');
    }
}
