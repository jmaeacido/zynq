<?php

namespace App\Domains\Reports\Http\Controllers;

use App\Domains\Reports\Services\TaxDiscountReportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class TaxReportController extends Controller
{
    public function vat(Request $request, TaxDiscountReportService $reports): View|StreamedResponse
    {
        $totals = $reports->vatSales($request->user(), $request->query('from'), $request->query('to'));

        if ($request->query('export') === 'csv') {
            return $this->csv('vat-sales-report.csv', $totals);
        }

        return view('reports.tax_summary', [
            'heading' => 'VAT Sales Report',
            'totals' => $totals,
        ]);
    }

    public function nonVat(Request $request, TaxDiscountReportService $reports): View|StreamedResponse
    {
        $totals = $reports->nonVatSales($request->user(), $request->query('from'), $request->query('to'));

        if ($request->query('export') === 'csv') {
            return $this->csv('non-vat-sales-report.csv', $totals);
        }

        return view('reports.tax_summary', [
            'heading' => 'Non-VAT Sales Report',
            'totals' => $totals,
        ]);
    }

    private function csv(string $filename, array $totals): StreamedResponse
    {
        return response()->streamDownload(function () use ($totals): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['metric', 'amount']);

            foreach ($totals as $key => $value) {
                fputcsv($handle, [$key, number_format((float) $value, 2, '.', '')]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
