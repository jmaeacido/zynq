<?php

namespace App\Domains\Sales\Http\Controllers;

use App\Domains\Branches\Models\Branch;
use App\Domains\Sales\Models\OfflineSaleSyncRecord;
use App\Domains\Sales\Services\OfflineSaleSyncService;
use App\Domains\Tenancy\Services\TenantContext;
use App\Domains\Terminals\Models\Terminal;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use InvalidArgumentException;

class OfflineSyncController extends Controller
{
    public function snapshot(Request $request, OfflineSaleSyncService $service): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'terminal_id' => ['required', 'exists:terminals,id'],
        ]);

        try {
            $snapshot = $service->snapshot(
                $request->user(),
                Branch::with('tenant')->findOrFail((int) $data['branch_id']),
                Terminal::findOrFail((int) $data['terminal_id']),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($snapshot);
    }

    public function store(Request $request, OfflineSaleSyncService $service): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'idempotency_key' => ['required', 'uuid'],
            'offline_reference' => ['required', 'string', 'max:255'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'terminal_id' => ['required', 'integer', 'exists:terminals,id'],
            'cashier_id' => ['required', 'integer', 'exists:users,id'],
            'cash_session_id' => ['required', 'integer', 'exists:cash_sessions,id'],
            'created_offline_at' => ['required', 'date'],
            'payload_hash' => ['required', 'string', 'size:64'],
            'tax_snapshot.vat_rate' => ['nullable', 'numeric'],
            'sale' => ['required', 'array'],
            'sale.items' => ['required', 'array', 'min:1'],
            'sale.items.*.product_id' => ['required', 'integer'],
            'sale.items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'sale.items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'sale.items.*.tax_type' => ['required', 'in:VATABLE,VAT_EXEMPT,ZERO_RATED,NON_VAT'],
            'sale.payments' => ['required', 'array', 'min:1'],
            'sale.payments.*.payment_method' => ['required', 'in:cash,card,e_wallet'],
            'sale.payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'sale.payments.*.amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'sale.payments.*.reference_number' => ['nullable', 'string', 'max:255'],
            'sale.discounts' => ['nullable', 'array'],
            'sale.discounts.*.discount_type' => ['required_with:sale.discounts', 'in:regular,promo,manual,senior,pwd,solo_parent'],
            'sale.discounts.*.value_type' => ['required_with:sale.discounts', 'in:amount,percent'],
            'sale.discounts.*.value' => ['required_with:sale.discounts', 'numeric', 'gt:0'],
            'sale.discounts.*.reason' => ['nullable', 'string', 'max:255'],
            'sale.discounts.*.reference_number' => ['nullable', 'string', 'max:255'],
            'sale.discounts.*.approved_by_user_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Offline sale payload is invalid.', 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $service->sync($request->user(), $validator->validated());
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $status = $result['status'] === 'conflict' ? 409 : 200;

        return response()->json($result, $status);
    }

    public function status(Request $request, TenantContext $context): JsonResponse|View
    {
        $records = $this->records($request, $context);

        if ($request->expectsJson()) {
            $lastSynced = $records->where('status', 'synced')->sortByDesc('synced_at')->first()?->synced_at;

            return response()->json([
                'pending_count' => $records->whereIn('status', ['pending', 'failed'])->count(),
                'conflict_count' => $records->where('status', 'conflict')->count(),
                'last_synced_at' => $lastSynced?->toIso8601String(),
                'items' => $records->take(50)->values(),
            ]);
        }

        return view('sync.status', ['records' => $records]);
    }

    public function conflicts(Request $request, TenantContext $context): View
    {
        return view('sync.conflicts', [
            'records' => $this->records($request, $context)->where('status', 'conflict')->values(),
        ]);
    }

    private function records(Request $request, TenantContext $context)
    {
        return $context->scopeForUser(OfflineSaleSyncRecord::query()->with(['sale', 'branch', 'terminal', 'cashier']), $request->user())
            ->latest()
            ->limit(100)
            ->get();
    }
}
