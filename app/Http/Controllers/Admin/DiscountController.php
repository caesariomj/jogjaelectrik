<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DiscountController extends Controller
{
    /**
     * Display a listing of the discount.
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->authorize('viewAny', Discount::class);

            return view('pages.admin.discounts.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Show the form for creating a new discount.
     */
    public function create(): View|RedirectResponse
    {
        try {
            $this->authorize('create', Discount::class);

            return view('pages.admin.discounts.create');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.discounts.index');
        }
    }

    /**
     * Display the specified discount.
     */
    public function show(string $code): View|RedirectResponse
    {
        $discount = (new Discount)->newFromBuilder(
            Discount::queryByCode(code: $code, columns: ['*'])->first()
        );

        if (! $discount) {
            session()->flash('error', 'Diskon tidak ditemukan.');

            return redirect()->route('admin.discounts.index');
        }

        try {
            $this->authorize('view', $discount);

            return view('pages.admin.discounts.show', compact('discount'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.discounts.index');
        }
    }

    /**
     * Show the form for editing the specified discount.
     */
    public function edit(string $code): View|RedirectResponse
    {
        $discount = (new Discount)->newFromBuilder(
            Discount::queryByCode(code: $code, columns: [
                'discounts.id',
                'discounts.name',
                'discounts.description',
                'discounts.code',
                'discounts.type',
                'discounts.value',
                'discounts.start_date',
                'discounts.end_date',
                'discounts.usage_limit',
                'discounts.used_count',
                'discounts.max_discount_amount',
                'discounts.minimum_purchase',
                'discounts.is_active',
            ])->first()
        );

        if (! $discount) {
            session()->flash('error', 'Diskon tidak ditemukan.');

            return redirect()->route('admin.discounts.index');
        }

        try {
            $this->authorize('update', $discount);

            return view('pages.admin.discounts.edit', compact('discount'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.discounts.index');
        }
    }
}
