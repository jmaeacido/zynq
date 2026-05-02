<?php

namespace App\Domains\Reports\Http\Controllers;

use App\Domains\Reports\Services\CashReadingReportService;
use App\Domains\Sales\Models\CashSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class CashReadingController extends Controller
{
    public function x(Request $request, CashSession $cashSession, CashReadingReportService $reports): View|StreamedResponse
    {
        $this->authorizeTenant($request, $cashSession);
        $reading = $reports->reading($cashSession);

        if ($request->query('export') === 'csv') {
            return $this->csv('x-reading-'.$cashSession->id.'.csv', $reading);
        }

        return view('readings.show', ['title' => 'X-Reading', 'reading' => $reading, 'session' => $cashSession]);
    }

    public function z(Request $request, CashSession $cashSession, CashReadingReportService $reports): View|StreamedResponse
    {
        $this->authorizeTenant($request, $cashSession);
        abort_unless($cashSession->status === 'closed', 422, 'Z-reading requires a closed cash session.');
        $reading = $reports->reading($cashSession);

        if ($request->query('export') === 'csv') {
            return $this->csv('z-reading-'.$cashSession->id.'.csv', $reading);
        }

        return view('readings.show', ['title' => 'Z-Reading', 'reading' => $reading, 'session' => $cashSession]);
    }

    public function cashier(Request $request, CashSession $cashSession, CashReadingReportService $reports): View|StreamedResponse
    {
        $this->authorizeTenant($request, $cashSession);
        $reading = $reports->reading($cashSession);

        if ($request->query('export') === 'csv') {
            return $this->csv('cashier-reading-'.$cashSession->id.'.csv', $reading);
        }

        return view('readings.show', ['title' => 'Cashier Reading Report', 'reading' => $reading, 'session' => $cashSession]);
    }

    public function terminal(Request $request, CashSession $cashSession, CashReadingReportService $reports): View|StreamedResponse
    {
        $this->authorizeTenant($request, $cashSession);
        $reading = $reports->reading($cashSession);

        if ($request->query('export') === 'csv') {
            return $this->csv('terminal-accountability-'.$cashSession->id.'.csv', $reading);
        }

        return view('readings.show', ['title' => 'Terminal Accountability Report', 'reading' => $reading, 'session' => $cashSession]);
    }

    public function daily(Request $request, CashReadingReportService $reports): View|StreamedResponse
    {
        $reading = $reports->dailySales($request->user(), $request->query('date'));

        if ($request->query('export') === 'csv') {
            return $this->csv('daily-sales-report.csv', $reading);
        }

        return view('readings.daily', ['reading' => $reading]);
    }

    private function authorizeTenant(Request $request, CashSession $cashSession): void
    {
        $user = $request->user();

        if (! $user->hasRole('Super Admin') && (int) $cashSession->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Reading does not belong to your tenant.');
        }
    }

    private function csv(string $filename, array $reading): StreamedResponse
    {
        return response()->streamDownload(function () use ($reading): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['metric', 'value']);

            foreach ($reading as $key => $value) {
                if (is_scalar($value) || $value === null) {
                    fputcsv($handle, [$key, $value]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
