<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Display a listing of the cart.
     */
    public function index(): View|RedirectResponse
    {
        $cart = Cart::queryByUserIdWithRelations(
            userId: auth()->id(),
            columns: [
                'carts.id',
                'carts.user_id',
                'cart_items.id as item_id',
                'cart_items.price',
                'cart_items.quantity',
                'products.id as product_id',
                'products.name',
                'products.slug',
                'products.weight',
                'categories.id as category_id',
                'subcategories.id as subcategory_id',
                'product_variants.variant_sku',
                'product_variants.stock',
                'categories.slug as category_slug',
                'subcategories.slug as subcategory_slug',
                'variation_variants.name as variant_name',
                'variations.name as variation_name',
                'product_images.file_name as thumbnail',
                'discounts.id as discount_id',
                'discounts.code as discount_code',
                'discounts.type as discount_type',
                'discounts.value as discount_value',
                'discounts.max_discount_amount as discount_max_discount_amount',
                'discounts.minimum_purchase as discount_minimum_purchase',
            ],
            relations: ['items', 'discount'],
        )
            ->get();

        $cart = $cart->isNotEmpty() ? $cart : Cart::create(['user_id' => auth()->id()]);

        if ($cart instanceof Cart) {
            $productRecommendations = collect();
        } elseif ($cart instanceof Collection) {
            if ($cart->first()->item_id === null) {
                $productRecommendations = collect();

                $cartData = $cart->first();

                $cart = (object) [
                    'id' => $cartData->id,
                    'user_id' => $cartData->user_id,
                    'total_weight' => 0,
                    'total_price' => 0,
                    'discount' => null,
                    'items' => null,
                ];
            } else {
                $cartItemCategoryIds = array_unique($cart->pluck('category_id')->toArray());
                $cartItemProductIds = $cart->pluck('product_id')->toArray();

                $productRecommendations = Product::queryAllWithRelations(columns: [
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
                    ->whereIn('categories.id', $cartItemCategoryIds)
                    ->whereNotIn('products.id', $cartItemProductIds)
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

                $cartData = $cart->first();

                $totalWeight = $cart->sum(function ($item) {
                    return $item->weight * $item->quantity;
                });

                $totalPrice = $cart->sum(function ($item) {
                    return $item->price * $item->quantity;
                });

                $cart = (object) [
                    'id' => $cartData->id,
                    'user_id' => $cartData->user_id,
                    'total_weight' => $totalWeight,
                    'total_price' => $totalPrice,
                    'discount' => (object) [
                        'id' => $cartData->discount_id,
                        'code' => $cartData->discount_code,
                        'type' => $cartData->discount_type,
                        'value' => $cartData->discount_value,
                        'max_discount_amount' => $cartData->discount_max_discount_amount,
                        'minimum_purchase' => $cartData->discount_minimum_purchase,
                    ],
                    'items' => $cart->map(function ($item) {
                        return (object) [
                            'id' => $item->item_id,
                            'name' => $item->name,
                            'thumbnail' => $item->thumbnail,
                            'slug' => $item->slug,
                            'category_slug' => $item->category_slug,
                            'subcategory_slug' => $item->subcategory_slug,
                            'price' => (float) $item->price,
                            'quantity' => (int) $item->quantity,
                            'stock' => (int) $item->stock,
                            'weight' => (float) $item->weight,
                            'variant' => $item->variant_name,
                            'variation' => $item->variation_name,
                        ];
                    }),
                ];

                if ($cart->discount->code === null) {
                    $cart->discount = null;
                }

                if ($cart->items->first()->id === null) {
                    $cart->items = null;
                }
            }

            if ($cart->discount) {
                $cart->discount = (new Discount)->newFromBuilder($cart->discount);
            }

            $cart = (new Cart)->newFromBuilder($cart);
        }

        try {
            $this->authorize('view', $cart);

            return view('pages.user.cart', compact('cart', 'productRecommendations'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('home');
        }
    }
}
