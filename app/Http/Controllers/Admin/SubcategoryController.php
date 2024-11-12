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
    public function index()
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
            session()->flash('error', 'Anda tidak memiliki izin untuk menambahkan subkategori baru.');

            return redirect()->route('admin.subcategories.index');
        }
    }

    /**
     * Store a newly created subcategory in storage.
     */
    public function store(array $validated)
    {
        try {
            $this->authorize('create', Subcategory::class);

            Subcategory::create([
                'category_id' => $validated['categoryId'],
                'name' => strtolower($validated['name']),
            ]);
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menambahkan subkategori baru.');

            return redirect()->route('admin.subcategories.index');
        }
    }

    /**
     * Display the specified subcategory.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $subcategory = Subcategory::with('category')->findBySlug($slug)->first();

        if (! $subcategory) {
            session()->flash('error', 'Data subkategori tidak ditemukan.');

            return redirect()->route('admin.subcategories.index');
        }

        try {
            $this->authorize('view', $subcategory);

            return view('pages.admin.subcategories.show', compact('subcategory'));
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk melihat detail subkategori ini.');

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
            session()->flash('error', 'Anda tidak memiliki izin untuk mengubah subkategori ini.');

            return redirect()->route('admin.subcategories.index');
        }
    }

    /**
     * Update the specified subcategory in storage.
     */
    public function update(array $validated, Subcategory $subcategory)
    {
        try {
            $this->authorize('update', $subcategory);

            $subcategory->update([
                'category_id' => $validated['categoryId'],
                'name' => strtolower($validated['name']),
            ]);
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mengubah subkategori ini.');

            return redirect()->route('admin.subcategories.index');
        }
    }

    /**
     * Remove the specified subcategory from storage.
     */
    public function destroy(Subcategory $subcategory)
    {
        try {
            $this->authorize('delete', $subcategory);

            $subcategory->delete();

            session()->flash('success', 'Subkategori '.$subcategory->name.' berhasil dihapus.');

            return redirect()->route('admin.subcategories.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menghapus subkategori ini.');

            return redirect()->route('admin.subcategories.index');
        }
    }
}
