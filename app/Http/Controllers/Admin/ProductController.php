<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the product.
     */
    public function index(): View|RedirectResponse
    {
        try {
            $this->authorize('viewAny', Product::class);

            return view('pages.admin.products.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View|RedirectResponse
    {
        try {
            $this->authorize('create', Product::class);

            return view('pages.admin.products.create');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.products.index');
        }
    }

    /**
     * Display the specified product.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $product = (new Product)->newFromBuilder(
            Product::queryBySlug(slug: $slug, columns: [
                'products.id',
                'products.subcategory_id',
                'products.name',
                'products.slug',
                'products.description',
                'products.main_sku',
                'products.cost_price',
                'products.base_price',
                'products.base_price_discount',
                'products.is_active',
                'products.warranty',
                'products.material',
                'products.dimension',
                'products.package',
                'products.weight',
                'products.power',
                'products.voltage',
            ], relations: [
                'category',
                'images',
                'variation',
                'aggregates',
            ])
        );

        if (! $product) {
            session()->flash('error', 'Produk tidak ditemukan.');

            return redirect()->route('admin.products.index');
        }

        try {
            $this->authorize('view', $product);

            return view('pages.admin.products.show', compact('product'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.products.index');
        }
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(string $slug)
    {
        $product = (new Product)->newFromBuilder(
            Product::queryBySlug(slug: $slug, columns: [
                'products.id',
                'products.subcategory_id',
                'products.name',
                'products.slug',
                'products.description',
                'products.main_sku',
                'products.cost_price',
                'products.base_price',
                'products.base_price_discount',
                'products.is_active',
                'products.warranty',
                'products.material',
                'products.dimension',
                'products.package',
                'products.weight',
                'products.power',
                'products.voltage',
            ], relations: [
                'category',
                'images',
                'variation',
                'aggregates',
            ])
        );

        if (! $product) {
            session()->flash('error', 'Produk tidak ditemukan.');

            return redirect()->route('admin.products.index');
        }

        try {
            $this->authorize('update', $product);

            return view('pages.admin.products.edit', compact('product'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.products.index');
        }
    }

    /**
     * Display a listing of the archived product.
     */
    public function archive(): View|RedirectResponse
    {
        try {
            $this->authorize('viewAny', Product::class);

            return view('pages.admin.products.archive');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }
}
