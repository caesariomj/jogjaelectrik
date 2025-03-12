<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Discount;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Crypt;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = Cart::queryByUserIdWithRelations(
            userId: auth()->id(),
            columns: [
                'carts.id',
                'carts.user_id',
                'cart_items.price',
                'cart_items.quantity',
                'products.id as product_id',
                'products.name',
                'products.slug',
                'products.weight',
                'categories.id as category_id',
                'subcategories.id as subcategory_id',
                'product_variants.id as product_variant_id',
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
                'users.name as user_name',
                'users.email as user_email',
                'users.phone_number as user_phone_number',
                'users.address as user_address',
                'users.postal_code as user_postal_code',
                'users.city_id as user_city_id',
                'cities.name as city_name',
                'cities.province_id as city_province_id',
                'provinces.name as province_name',
            ],
            relations: ['items', 'discount', 'user'],
        )
            ->get();

        if (! $cart || ! $cart->first()->product_variant_id) {
            session()->flash('error', 'Keranjang belanja Anda kosong. Tambahkan produk ke keranjang sebelum melanjutkan ke proses checkout.');

            return redirect()->back();
        }

        $totalWeight = $cart->sum(function ($item) {
            return $item->weight * $item->quantity;
        });

        if ($totalWeight >= 30000) {
            session()->flash('error', 'Total berat produk di keranjang Anda melebihi batas maksimum 30.000 gram (30 kg).');

            return redirect()->back();
        }

        $totalPrice = $cart->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        if ($cart->first()->discount_minimum_purchase && ($cart->first()->discount_minimum_purchase > $totalPrice)) {
            session()->flash('error', 'Minimal pembelian Rp '.formatPrice($cart->first()->discount_minimum_purchase).' diperlukan untuk menggunakan diskon ini.');

            return redirect()->back();
        }

        $cartData = $cart->first();

        $cart = (object) [
            'id' => $cartData->id,
            'user_id' => $cartData->user_id,
            'total_weight' => $totalWeight,
            'total_price' => $totalPrice,
            'user' => (object) [
                'id' => $cartData->user_id,
                'name' => $cartData->user_name,
                'email' => $cartData->user_email,
                'phone_number' => $cartData->user_phone_number ? Crypt::decryptString($cartData->user_phone_number) : null,
                'address' => $cartData->user_address ? Crypt::decryptString($cartData->user_address) : null,
                'postal_code' => $cartData->user_postal_code ? Crypt::decryptString($cartData->user_postal_code) : null,
                'city_id' => $cartData->user_city_id,
                'city_name' => $cartData->city_name,
                'province_id' => $cartData->city_province_id,
                'province_name' => $cartData->province_name,
            ],
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
                    'id' => $item->product_variant_id,
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

        if ($cart->discount) {
            $cart->discount = (new Discount)->newFromBuilder($cart->discount);
        }

        $cart->user = (new User)->newFromBuilder($cart->user);

        $cart = (new Cart)->newFromBuilder($cart);

        try {
            $this->authorize('checkout', $cart);

            return view('pages.user.checkout', compact('cart'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('home');
        }
    }
}
