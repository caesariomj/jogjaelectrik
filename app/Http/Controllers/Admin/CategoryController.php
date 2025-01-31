<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of the category.
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->authorize('viewAny', Category::class);

            return view('pages.admin.categories.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): View|RedirectResponse
    {
        try {
            $this->authorize('create', Category::class);

            return view('pages.admin.categories.create');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.categories.index');
        }
    }

    /**
     * Display the specified category.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $category = (new Category)->newFromBuilder(
            Category::queryBySlugWithTotalSubcategoryAndProduct(slug: $slug)->first()
        );

        if (! $category) {
            session()->flash('error', 'Kategori tidak ditemukan.');

            return redirect()->route('admin.categories.index');
        }

        try {
            $this->authorize('view', $category);

            return view('pages.admin.categories.show', compact('category'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.categories.index');
        }
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(string $slug): View|RedirectResponse
    {
        $category = (new Category)->newFromBuilder(
            Category::queryBySlug(slug: $slug, columns: [
                'categories.id',
                'categories.name',
                'categories.is_primary',
            ])->first()
        );

        if (! $category) {
            session()->flash('error', 'Kategori tidak ditemukan.');

            return redirect()->route('admin.categories.index');
        }

        try {
            $this->authorize('update', $category);

            return view('pages.admin.categories.edit', compact('category'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.categories.index');
        }
    }
}
