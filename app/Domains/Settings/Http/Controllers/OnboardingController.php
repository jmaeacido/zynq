<?php

namespace App\Domains\Settings\Http\Controllers;

use App\Domains\Branches\Models\Branch;
use App\Domains\Settings\Services\TenantSetupService;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantContext;
use App\Domains\Terminals\Models\Terminal;
use App\Domains\Terminals\Services\TerminalComplianceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function index(Request $request, TenantContext $context, TerminalComplianceService $compliance): View
    {
        $tenants = $context->scopeForUser(Tenant::with(['branches', 'terminals']), $request->user())->orderBy('business_name')->get();

        return view('onboarding.index', [
            'tenants' => $tenants,
            'compliance' => $compliance,
            'requiredDocsReady' => $this->requiredDocsReady(),
        ]);
    }

    public function branches(Request $request, TenantContext $context): View
    {
        return view('onboarding.branches', [
            'branches' => $context->scopeForUser(Branch::with('tenant')->latest(), $request->user())->paginate(20),
        ]);
    }

    public function terminals(Request $request, TenantContext $context, TerminalComplianceService $compliance): View
    {
        return view('onboarding.terminals', [
            'terminals' => $context->scopeForUser(Terminal::with(['tenant', 'branch'])->latest(), $request->user())->paginate(20),
            'compliance' => $compliance,
        ]);
    }

    public function complete(Request $request, Tenant $tenant, TenantSetupService $setup): RedirectResponse
    {
        $this->authorizeTenant($request, $tenant);
        $setup->completeOnboarding($tenant, $request->user());

        return back()->with('status', 'Tenant onboarding marked complete.');
    }

    private function authorizeTenant(Request $request, Tenant $tenant): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $tenant->id !== (int) $user->tenant_id) {
            abort(403, 'Tenant setup is outside your tenant.');
        }
    }

    private function requiredDocsReady(): bool
    {
        $required = [
            'docs/OFFLINE_SYNC_PLAN.md',
            'docs/DEPLOYMENT_MODES.md',
            'docs/UPDATE_AND_UPGRADE_PLAN.md',
            'docs/BIR_COMPLIANCE_MATRIX.md',
            'docs/SYSTEM_DESCRIPTION.md',
            'docs/USER_MANUAL.md',
            'docs/ADMIN_MANUAL.md',
            'docs/BIR_SUBMISSION_PREP.md',
            'docs/DATABASE_DICTIONARY.md',
            'docs/TESTING_CHECKLIST.md',
            'docs/bir_samples/sample_invoice_output.txt',
            'docs/bir_samples/sample_z_reading_report.csv',
            'docs/bir_samples/sample_sales_journal_export.csv',
            'docs/bir_samples/sample_audit_log_export.csv',
            'docs/bir_samples/sample_terminal_compliance_checklist.csv',
            'docs/bir_samples/sample_checksum_validation_notes.md',
        ];

        return collect($required)->every(fn (string $path): bool => File::exists(base_path($path)));
    }
}
