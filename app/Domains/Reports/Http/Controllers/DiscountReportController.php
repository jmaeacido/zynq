<?php

namespace App\Domains\Reports\Http\Controllers;

use App\Domains\Reports\Services\TaxDiscountReportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class DiscountReportController extends Controller
{
    public function __invoke(Request $request, TaxDiscountReportService $reports): View|StreamedResponse
    {
        $rows = $reports->discounts($request->user(), $request->query('from'), $request->query('to'));

        if ($request->query('export') === 'csv') {
            return response()->streamDownload(function () use ($rows): void {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['discount_type', 'count', 'total_amount']);

                foreach ($rows as $row) {
                    fputcsv($handle, [$row->discount_type, $row->count, number_format((float) $row->total_amount, 2, '.', '')]);
                }

                fclose($handle);
            }, 'discount-report.csv', ['Content-Type' => 'text/csv']);
        }

        return view('reports.discounts', [
            'rows' => $rows,
        ]);
    }
}
