<?php

namespace App\Domains\Reports\Http\Controllers;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Sales\Models\SaleReversal;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReversalReportController extends Controller
{
    public function voids(Request $request): View|StreamedResponse
    {
        $query = $this->query($request, ['void']);

        if ($request->query('export') === 'csv') {
            return $this->reversalCsv('void-report.csv', $query->get());
        }

        return view('reports.reversals', ['heading' => 'Void Report', 'rows' => $query->paginate(30)]);
    }

    public function refunds(Request $request): View|StreamedResponse
    {
        $query = $this->query($request, ['refund', 'partial_refund']);

        if ($request->query('export') === 'csv') {
            return $this->reversalCsv('refund-report.csv', $query->get());
        }

        return view('reports.reversals', ['heading' => 'Refund Report', 'rows' => $query->paginate(30)]);
    }

    public function audit(Request $request): View|StreamedResponse
    {
        $query = AuditLog::query()
            ->when(! $request->user()->hasRole('Super Admin'), fn ($q) => $q->where('tenant_id', $request->user()->tenant_id))
            ->latest();

        if ($request->query('export') === 'csv') {
            return response()->streamDownload(function () use ($query): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['created_at', 'tenant_id', 'branch_id', 'user_id', 'action', 'module', 'record_type', 'record_id']);

                foreach ($query->get() as $row) {
                    fputcsv($handle, [$row->created_at, $row->tenant_id, $row->branch_id, $row->user_id, $row->action, $row->module, $row->record_type, $row->record_id]);
                }

                fclose($handle);
            }, 'audit-trail-report.csv', ['Content-Type' => 'text/csv']);
        }

        $rows = $query->paginate(30);

        return view('reports.audit', compact('rows'));
    }

    private function query(Request $request, array $types)
    {
        return SaleReversal::with(['sale', 'approver'])
            ->whereIn('type', $types)
            ->when(! $request->user()->hasRole('Super Admin'), fn ($q) => $q->where('tenant_id', $request->user()->tenant_id))
            ->latest();
    }

    private function reversalCsv(string $filename, $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['created_at', 'tenant_id', 'branch_id', 'terminal_id', 'invoice_number', 'type', 'amount', 'reason', 'approver_id', 'transaction_hash']);

            foreach ($rows as $row) {
                fputcsv($handle, [$row->created_at, $row->tenant_id, $row->branch_id, $row->terminal_id, $row->original_invoice_number, $row->type, $row->amount, $row->reason, $row->approved_by, $row->transaction_hash]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
