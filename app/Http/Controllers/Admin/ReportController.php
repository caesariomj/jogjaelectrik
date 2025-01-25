<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(): View|RedirectResponse
    {
        if (! auth()->user()->can('view reports')) {
            session()->flash('error', 'Anda tidak memiliki izin untuk melihat laporan penjualan.');

            return redirect()->route('admin.dashboard');
        }

        return view('pages.admin.reports.sales');
    }
}
