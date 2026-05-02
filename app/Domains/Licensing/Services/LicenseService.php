<?php

namespace App\Domains\Licensing\Services;

use App\Domains\Audit\Services\AuditService;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicenseService
{
    public const STATUSES = ['trial', 'active', 'grace_period', 'suspended', 'disabled'];

    public function __construct(private readonly AuditService $audit)
    {
    }

    public function update(Tenant $tenant, array $data, User $user): Tenant
    {
        $data['license_key'] = $this->normalizeKey($data['license_key'] ?? null);

        if ($data['license_key'] && ! $this->isValidKey($data['license_key'])) {
            throw ValidationException::withMessages([
                'license_key' => 'License key must be 12-64 characters using letters, numbers, and dashes.',
            ]);
        }

        return DB::transaction(function () use ($tenant, $data, $user): Tenant {
            $old = $tenant->only([
                'license_key',
                'license_status',
                'subscription_expires_at',
                'grace_period_days',
                'active',
            ]);

            $tenant->update([
                'license_key' => $data['license_key'] ?: null,
                'license_status' => $data['license_status'],
                'subscription_expires_at' => $data['subscription_expires_at'] ?? null,
                'grace_period_days' => (int) ($data['grace_period_days'] ?? 0),
                'active' => (bool) ($data['active'] ?? false),
            ]);

            $this->audit->record(
                $user,
                'updated',
                'licensing',
                Tenant::class,
                $tenant->id,
                $tenant->fresh()->only(array_keys($old)),
                $old,
                $tenant->id,
            );

            return $tenant->refresh();
        });
    }

    public function normalizeKey(?string $key): ?string
    {
        $key = trim((string) $key);

        return $key === '' ? null : strtoupper($key);
    }

    public function isValidKey(string $key): bool
    {
        return (bool) preg_match('/^[A-Z0-9-]{12,64}$/', $key);
    }

    public function status(Tenant $tenant): array
    {
        if (! $tenant->active) {
            return $this->blocked('Tenant is inactive.');
        }

        if (in_array($tenant->license_status, ['disabled', 'suspended'], true)) {
            return $this->blocked("Tenant license is {$tenant->license_status}.");
        }

        if (! $tenant->subscription_expires_at) {
            return ['blocked' => false, 'warning' => null, 'state' => $tenant->license_status];
        }

        $today = Carbon::today();
        $expiry = $tenant->subscription_expires_at->copy()->startOfDay();

        if ($expiry->greaterThanOrEqualTo($today)) {
            return ['blocked' => false, 'warning' => null, 'state' => $tenant->license_status];
        }

        $graceEnds = $expiry->copy()->addDays((int) $tenant->grace_period_days);

        if ($tenant->grace_period_days > 0 && $graceEnds->greaterThanOrEqualTo($today)) {
            return [
                'blocked' => false,
                'warning' => 'Subscription expired on '.$expiry->toDateString().' and is operating inside grace period until '.$graceEnds->toDateString().'.',
                'state' => 'grace_period',
            ];
        }

        return $this->blocked('Subscription expired on '.$expiry->toDateString().' and grace period has ended.');
    }

    public function assertOperational(Tenant $tenant): void
    {
        $status = $this->status($tenant);

        abort_if($status['blocked'], 403, $status['warning'] ?? 'Tenant is not operational.');
    }

    private function blocked(string $message): array
    {
        return ['blocked' => true, 'warning' => $message, 'state' => 'blocked'];
    }
}
