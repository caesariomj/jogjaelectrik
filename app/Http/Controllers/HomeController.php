<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Displays the main page.
     */
    public function index(): View
    {
        return view('pages.home');
    }

    /**
     * Displays the product detail page.
     */
    public function productDetail(string $slug): View
    {
        $product = Product::with(['subcategory.category', 'images', 'variants.combinations.variationVariant.variation', 'reviews'])->findBySlug($slug)->firstOrFail();

        $productRecommendations = Product::whereHas('subcategory', function ($query) use ($product) {
            if ($product->subcategory) {
                return $query->where('category_id', $product->subcategory->category->id);
            }
        })
            ->where('id', '!=', $product->id)
            ->active()
            ->limit(6)
            ->get();

        $productRecommendations = ProductResource::collection($productRecommendations)->toArray(request());

        return view('pages.product-detail', compact('product', 'productRecommendations'));
    }

    /**
     * Displays the faq page.
     */
    public function faq(): View
    {
        return view('pages.faq');
    }
}
