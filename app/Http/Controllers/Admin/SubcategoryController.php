<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of the subcategory.
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->authorize('viewAny', Subcategory::class);

            return view('pages.admin.subcategories.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Show the form for creating a new subcategory.
     */
    public function create(): View|RedirectResponse
    {
        try {
            $this->authorize('create', Subcategory::class);

            return view('pages.admin.subcategories.create');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.subcategories.index');
        }
    }

    /**
     * Display the specified subcategory.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $subcategory = (new Subcategory)->newFromBuilder(
            Subcategory::queryBySlug(slug: $slug, columns: [
                'id',
                'category_id',
                'name',
                'created_at',
                'updated_at',
            ], relations: [
                'category',
                'aggregates',
            ])
        );

        if (! $subcategory) {
            session()->flash('error', 'Subkategori tidak ditemukan.');

            return redirect()->route('admin.subcategories.index');
        }

        try {
            $this->authorize('view', $subcategory);

            return view('pages.admin.subcategories.show', compact('subcategory'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.subcategories.index');
        }
    }

    /**
     * Show the form for editing the specified subcategory.
     */
    public function edit(string $slug): View|RedirectResponse
    {
        $subcategory = (new Subcategory)->newFromBuilder(
            Subcategory::queryBySlug(slug: $slug, columns: [
                'id',
                'category_id',
                'name',
            ])
        );

        if (! $subcategory) {
            session()->flash('error', 'Subkategori tidak ditemukan.');

            return redirect()->route('admin.subcategories.index');
        }

        try {
            $this->authorize('update', $subcategory);

            return view('pages.admin.subcategories.edit', compact('subcategory'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.subcategories.index');
        }
    }
}
