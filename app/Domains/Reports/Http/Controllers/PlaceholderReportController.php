<?php

namespace App\Domains\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlaceholderReportController extends Controller
{
    public function __invoke(string $type): View
    {
        abort_unless(in_array($type, ['senior', 'pwd', 'solo-parent'], true), 404);

        return view('reports.placeholder', ['type' => $type]);
    }
}
