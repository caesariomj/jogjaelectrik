<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaleController extends Controller
{
    /**
     * Display a listing of the sale.
     */
    public function index(): View|RedirectResponse
    {
        return view('pages.admin.sales.index');
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create(): View|RedirectResponse
    {
        try {
            // $this->authorize('create', Order::class);

            return view('pages.admin.sales.create');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.sales.index');
        }
    }
}
