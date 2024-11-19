<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DiscountController extends Controller
{
    /**
     * Display a listing of the discount.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Discount::class);

        return view('pages.admin.discounts.index');
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
            session()->flash('error', 'Anda tidak memiliki izin untuk menambahkan diskon baru.');

            return redirect()->route('admin.discounts.index');
        }
    }

    /**
     * Store a newly created discount in storage.
     */
    public function store(array $validated)
    {
        try {
            $this->authorize('create', Discount::class);

            Discount::create([
                'name' => strtolower($validated['name']),
                'description' => $validated['description'] !== '' ? $validated['description'] : null,
                'code' => Str::slug($validated['code']),
                'type' => $validated['type'],
                'value' => (int) str_replace('.', '', $validated['value']),
                'start_date' => $validated['startDate'] !== '' ? $validated['startDate'] : null,
                'end_date' => $validated['endDate'] !== '' ? $validated['endDate'] : null,
                'usage_limit' => is_numeric($validated['usageLimit']) ? (int) str_replace('.', '', $validated['usageLimit']) : null,
                'minimum_purchase' => is_numeric($validated['minimumPurchase']) ? (int) str_replace('.', '', $validated['minimumPurchase']) : null,
                'is_active' => (bool) $validated['isActive'],
            ]);
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menambahkan diskon baru.');

            return redirect()->route('admin.discounts.index');
        }
    }

    /**
     * Display the specified discount.
     */
    public function show(string $code): View|RedirectResponse
    {
        $discount = Discount::findByCode($code)->first();

        if (! $discount) {
            session()->flash('error', 'Data diskon tidak ditemukan.');

            return redirect()->route('admin.discounts.index');
        }

        try {
            $this->authorize('view', $discount);

            return view('pages.admin.discounts.show', compact('discount'));
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk melihat detail diskon ini.');

            return redirect()->route('admin.discounts.index');
        }
    }

    /**
     * Show the form for editing the specified discount.
     */
    public function edit(string $code): View|RedirectResponse
    {
        $discount = Discount::findByCode($code)->first();

        if (! $discount) {
            session()->flash('error', 'Data diskon tidak ditemukan.');

            return redirect()->route('admin.discounts.index');
        }

        try {
            $this->authorize('update', $discount);

            return view('pages.admin.discounts.edit', compact('discount'));
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mengubah diskon ini.');

            return redirect()->route('admin.discounts.index');
        }
    }

    /**
     * Update the specified discount in storage.
     */
    public function update(array $validated, Discount $discount)
    {
        try {
            $this->authorize('update', $discount);

            $discount->update([
                'name' => strtolower($validated['name']),
                'description' => $validated['description'] !== '' ? $validated['description'] : null,
                'code' => Str::slug($validated['code']),
                'type' => $validated['type'],
                'value' => (int) str_replace('.', '', $validated['value']),
                'start_date' => $validated['startDate'] !== '' ? $validated['startDate'] : null,
                'end_date' => $validated['endDate'] !== '' ? $validated['endDate'] : null,
                'usage_limit' => is_numeric($validated['usageLimit']) ? (int) str_replace('.', '', $validated['usageLimit']) : null,
                'minimum_purchase' => is_numeric($validated['minimumPurchase']) ? (int) str_replace('.', '', $validated['minimumPurchase']) : null,
                'is_active' => (bool) $validated['isActive'],
            ]);
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mengubah diskon ini.');

            return redirect()->route('admin.discounts.index');
        }
    }

    /**
     * Update the specified discount in storage.
     */
    public function resetUsage(Discount $discount)
    {
        if ($discount->used_count === 0) {
            session()->flash('error', 'Penggunaan diskon tidak dapat direset karena diskon belum pernah digunakan.');

            return redirect()->route('admin.discounts.index');
        }

        try {
            $this->authorize('manage', $discount);

            $discount->update([
                'used_count' => (int) 0,
            ]);

            session()->flash('success', 'Penggunaan diskon '.$discount->name.'berhasil direset.');

            return redirect()->route('admin.discounts.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mereset penggunaan diskon ini.');

            return redirect()->route('admin.discounts.index');
        }
    }

    /**
     * Remove the specified discount from storage.
     */
    public function destroy(Discount $discount): RedirectResponse
    {
        try {
            $this->authorize('delete', $discount);

            $discount->delete();

            session()->flash('success', 'Diskon '.$discount->name.' berhasil dihapus.');

            return redirect()->route('admin.discounts.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menghapus diskon ini.');

            return redirect()->route('admin.discounts.index');
        }
    }
}
