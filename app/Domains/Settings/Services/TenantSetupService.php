<?php

namespace App\Domains\Settings\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TenantSetupService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly TenantSettingsService $settings,
    ) {
    }

    public function updateBirInfo(Tenant $tenant, array $data, User $user): Tenant
    {
        return DB::transaction(function () use ($tenant, $data, $user): Tenant {
            $old = $tenant->only(['registered_address', 'tin', 'taxpayer_type', 'bir_rdo_code']);

            $tenant->update([
                'registered_address' => $data['registered_address'],
                'tin' => $data['tin'],
                'taxpayer_type' => $data['taxpayer_type'],
                'bir_rdo_code' => $data['bir_rdo_code'],
            ]);

            $this->settings->set($tenant, 'bir_registration_notes', ['notes' => $data['bir_registration_notes'] ?? null]);
            $this->audit->record($user, 'updated', 'bir_info', Tenant::class, $tenant->id, $tenant->fresh()->only(array_keys($old)), $old, $tenant->id);

            return $tenant->refresh();
        });
    }

    public function updateInvoiceSettings(Tenant $tenant, array $data, User $user, ?UploadedFile $logo = null): Tenant
    {
        return DB::transaction(function () use ($tenant, $data, $user, $logo): Tenant {
            $old = $tenant->only(['trade_name', 'logo_path', 'invoice_footer']);
            $payload = [
                'trade_name' => $data['trade_name'] ?? $tenant->trade_name,
                'invoice_footer' => $data['invoice_footer'] ?? null,
            ];

            if ($logo) {
                $payload['logo_path'] = $logo->store('tenant-logos', 'public');
            }

            $tenant->update($payload);
            $this->audit->record($user, 'updated', 'invoice_settings', Tenant::class, $tenant->id, $tenant->fresh()->only(array_keys($old)), $old, $tenant->id);

            return $tenant->refresh();
        });
    }

    public function completeOnboarding(Tenant $tenant, User $user): Tenant
    {
        return DB::transaction(function () use ($tenant, $user): Tenant {
            $old = ['onboarding_completed_at' => $tenant->onboarding_completed_at];

            $tenant->update(['onboarding_completed_at' => now()]);
            $this->audit->record($user, 'completed', 'onboarding', Tenant::class, $tenant->id, [
                'onboarding_completed_at' => $tenant->fresh()->onboarding_completed_at?->toISOString(),
            ], $old, $tenant->id);

            return $tenant->refresh();
        });
    }
}
