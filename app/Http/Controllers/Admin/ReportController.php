<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(): View|RedirectResponse
    {
        return view('pages.admin.reports.sales');
    }
}
