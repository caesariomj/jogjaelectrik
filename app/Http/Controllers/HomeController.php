<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Displays the main page.
     */
    public function index(): View
    {
        $primaryCategories = Category::queryPrimary()->get();

        $bannerSlides = $primaryCategories->map(function ($category, $key) {
            return (object) [
                'imgSrc' => $key % 2 === 0 ? 'https://penguinui.s3.amazonaws.com/component-assets/carousel/default-slide-1.webp' : 'https://penguinui.s3.amazonaws.com/component-assets/carousel/default-slide-2.webp',
                'imgAlt' => 'Banner kategori '.$category->name.'.',
                'title' => ucwords($category->name),
                'description' => $key % 2 === 0 ? 'Jelajahi koleksi '.$category->name.' dengan pilihan terbaik untuk memenuhi kebutuhan Anda.' : 'Temukan berbagai pilihan '.$category->name.' yang siap melengkapi kebutuhan Anda dengan kualitas terbaik.',
                'ctaUrl' => route('products.category', ['category' => $category->slug]),
                'ctaText' => 'Jelajahi Produk '.ucwords($category->name),
            ];
        });

        $bestSellingProducts = Product::queryAllWithRelations(columns: [
            'products.id',
            'products.name',
            'products.slug',
            'products.base_price',
            'products.base_price_discount',
        ], relations: [
            'thumbnail',
            'category',
            'rating',
        ])
            ->where('products.is_active', true)
            ->limit(8)
            ->orderByDesc(
                DB::table('order_details')
                    ->join('product_variants', 'product_variants.id', '=', 'order_details.product_variant_id')
                    ->selectRaw('COALESCE(SUM(order_details.quantity), 0)')
                    ->whereColumn('product_variants.product_id', 'products.id')
            )
            ->get()
            ->map(function ($product) {
                return (object) [
                    'id' => $product->id,
                    'name' => $product->name,
                    'link' => $product->category_slug && $product->subcategory_slug ? route('products.detail', ['category' => $product->category_slug, 'subcategory' => $product->subcategory_slug, 'slug' => $product->slug]) : route('products.detail.without.category.subcategory', ['slug' => $product->slug]),
                    'price' => $product->base_price,
                    'price_discount' => $product->base_price_discount,
                    'thumbnail' => asset('storage/uploads/product-images/'.$product->thumbnail),
                    'rating' => number_format($product->average_rating, 1),
                ];
            });

        $activeDiscount = Discount::queryAllUsable(
            userId: auth()->check() ? auth()->user()->id : null,
            columns: [
                'id',
                'name',
                'description',
                'code',
                'type',
                'value',
                'max_discount_amount',
                'end_date',
            ])
            ->when(auth()->check() && auth()->user()->cart()->exists(), function ($query) {
                $query->where('id', '!=', auth()->user()->cart->discount_id);
            })
            ->first();

        $activeDiscount = $activeDiscount ? (new Discount)->newFromBuilder($activeDiscount) : null;

        $latestProducts = Product::queryAllWithRelations(columns: [
            'products.id',
            'products.name',
            'products.slug',
            'products.base_price',
            'products.base_price_discount',
        ], relations: [
            'thumbnail',
            'category',
            'rating',
        ])
            ->where('products.is_active', true)
            ->limit(8)
            ->orderByDesc('products.created_at')
            ->get()
            ->map(function ($product) {
                return (object) [
                    'id' => $product->id,
                    'name' => $product->name,
                    'link' => $product->category_slug && $product->subcategory_slug ? route('products.detail', ['category' => $product->category_slug, 'subcategory' => $product->subcategory_slug, 'slug' => $product->slug]) : route('products.detail.without.category.subcategory', ['slug' => $product->slug]),
                    'price' => $product->base_price,
                    'price_discount' => $product->base_price_discount,
                    'thumbnail' => asset('storage/uploads/product-images/'.$product->thumbnail),
                    'rating' => number_format($product->average_rating, 1),
                ];
            });

        return view('pages.home', compact('bannerSlides', 'bestSellingProducts', 'activeDiscount', 'latestProducts'));
    }

    /**
     * Displays the products page.
     */
    public function products(Request $request, string $category = '', string $subcategory = ''): View|RedirectResponse
    {
        $validatedSearch = $request->validate([
            'q' => ['sometimes', 'string', 'max:255'],
        ]);

        $search = isset($validatedSearch['q']) ? $validatedSearch['q'] : '';

        $categoryAndSubcategoryValidator = validator([
            'category' => $category,
            'subcategory' => $subcategory,
        ], [
            'category' => ['sometimes', 'string', 'lowercase', 'max:255', 'exists:categories,slug'],
            'subcategory' => ['sometimes', 'string', 'lowercase', 'max:255', 'exists:subcategories,slug'],
        ]);

        if ($categoryAndSubcategoryValidator->fails()) {
            session()->flash('error', $subcategory ? 'Produk dengan subkategori '.str_replace('-', ' ', $subcategory).' tidak ditemukan' : ($category ? 'Produk dengan kategori '.str_replace('-', ' ', $category).' tidak ditemukan' : 'Produk tidak ditemukan'));

            return redirect()->route('home');
        }

        $validatedCategoryAndSubcategory = $categoryAndSubcategoryValidator->validated();

        $category = $validatedCategoryAndSubcategory['category'] ?? '';

        $subcategory = $validatedCategoryAndSubcategory['subcategory'] ?? '';

        return view('pages.products', compact('category', 'subcategory', 'search'));
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
