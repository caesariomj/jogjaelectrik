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
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('pages.admin.products.index');
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
        $product = Product::with(['images', 'subcategory.category'])
            ->withSum(
                [
                    'orderDetails as total_sold' => function ($query) {
                        $query->whereHas('order', function ($q) {
                            $q->where('status', 'completed');
                        });
                    },
                ],
                'quantity',
            )
            ->findBySlug($slug)
            ->first();

        if (! $product) {
            session()->flash('error', 'Data produk tidak ditemukan.');

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
        $product = Product::with(['images', 'variants.combinations.variationVariant.variation'])->findBySlug($slug)->first();

        if (! $product) {
            session()->flash('error', 'Data produk tidak ditemukan.');

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
    public function archive(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('pages.admin.products.archive');
    }
}
