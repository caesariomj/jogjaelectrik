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
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        return view('pages.admin.categories.index');
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
            session()->flash('error', 'Anda tidak memiliki izin untuk menambahkan kategori baru.');

            return redirect()->route('admin.categories.index');
        }
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(array $validated)
    {
        try {
            $this->authorize('create', Category::class);

            Category::create([
                'name' => strtolower($validated['name']),
                'is_primary' => $validated['isPrimary'],
            ]);
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menambahkan kategori baru.');

            return redirect()->route('admin.categories.index');
        }
    }

    /**
     * Display the specified category.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $category = Category::findBySlug($slug)->first();

        if (! $category) {
            session()->flash('error', 'Data kategori tidak ditemukan.');

            return redirect()->route('admin.categories.index');
        }

        try {
            $this->authorize('view', $category);

            return view('pages.admin.categories.show', compact('category'));
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk melihat detail kategori ini.');

            return redirect()->route('admin.categories.index');
        }
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(string $slug): View|RedirectResponse
    {
        $category = Category::findBySlug($slug)->first();

        if (! $category) {
            session()->flash('error', 'Data kategori tidak ditemukan.');

            return redirect()->route('admin.categories.index');
        }

        try {
            $this->authorize('update', $category);

            return view('pages.admin.categories.edit', compact('category'));
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mengubah kategori ini.');

            return redirect()->route('admin.categories.index');
        }
    }

    /**
     * Update the specified category in storage.
     */
    public function update(array $validated, Category $category)
    {
        try {
            $this->authorize('update', $category);

            $category->update([
                'name' => strtolower($validated['name']),
                'is_primary' => $validated['isPrimary'],
            ]);
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mengubah kategori ini.');

            return redirect()->route('admin.categories.index');
        }
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category)
    {
        try {
            $this->authorize('delete', $category);

            $category->delete();

            session()->flash('success', 'Kategori '.$category->name.' berhasil dihapus.');

            return redirect()->route('admin.categories.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menghapus kategori ini.');

            return redirect()->route('admin.categories.index');
        }
    }
}
