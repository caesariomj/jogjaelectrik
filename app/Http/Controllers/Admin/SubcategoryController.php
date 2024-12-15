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
    public function index(): View
    {
        $this->authorize('viewAny', Subcategory::class);

        return view('pages.admin.subcategories.index');
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
        $subcategory = Subcategory::with('category')->withCount('products')->findBySlug($slug)->first();

        if (! $subcategory) {
            session()->flash('error', 'Data subkategori tidak ditemukan.');

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
        $subcategory = Subcategory::findBySlug($slug)->first();

        if (! $subcategory) {
            session()->flash('error', 'Data subkategori tidak ditemukan.');

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
