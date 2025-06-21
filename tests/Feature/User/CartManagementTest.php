<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);

    $this->user = $user;
});

test('cart screen can be rendered', function () {
    $response = $this->get('/keranjang-belanja');

    $response
        ->assertOk()
        ->assertSeeVolt('user.cart-item-list');
});

test('product can be added to cart', function () {
    $product = Product::factory()->create();

    $productDetailUrl = '/produk/detail/'.$product->subcategory->category->slug.'/'.$product->subcategory->slug.'/'.$product->slug;
    $response = $this->get($productDetailUrl);

    $response
        ->assertOk()
        ->assertSeeVolt('product-detail-section');

    $product = Product::queryBySlug(slug: $product->slug, columns: [
        'products.id',
        'products.subcategory_id',
        'products.name',
        'products.slug',
        'products.description',
        'products.main_sku',
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
        'reviews',
        'aggregates',
    ]);

    $product->images = collect($product->images);

    $product = (new Product)->newFromBuilder($product);

    $component = Volt::test('product-detail-section', ['product' => $product]);

    $selectedVariantId = $component->get('selectedVariantId');

    expect($selectedVariantId)->not->toBeNull();

    $component
        ->call('addToCart')
        ->assertRedirect($productDetailUrl);

    $cart = DB::table('carts')->where('user_id', $this->user->id)->first();

    expect($cart)->not->toBeNull();

    $this->assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'product_variant_id' => $selectedVariantId,
        'quantity' => 1,
    ]);
});

test('cart increment button works', function () {
    $product = Product::factory()->create();

    $product = Product::queryBySlug(slug: $product->slug, columns: [
        'products.id',
        'products.subcategory_id',
        'products.name',
        'products.slug',
        'products.description',
        'products.main_sku',
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
        'reviews',
        'aggregates',
    ]);

    $product->images = collect($product->images);

    $product = (new Product)->newFromBuilder($product);

    $firstProductVariant = $product->variation->variants[0];
    $firstProductVariantPrice = $firstProductVariant->price_discount ? $firstProductVariant->price_discount : $firstProductVariant->price;

    $cart = $this->user->cart()->create();

    expect($cart)->not->toBeNull();

    $cart->items()->create([
        'product_variant_id' => $firstProductVariant->id,
        'price' => $firstProductVariantPrice * 2,
        'quantity' => 2,
    ]);

    expect($cart->items->pluck('product_variant_id'))->toContain($firstProductVariant->id);

    $response = $this->get('/keranjang-belanja');

    $response
        ->assertOk()
        ->assertSeeVolt('user.cart-item-list');

    $cart = Cart::queryByUserIdWithRelations(
        userId: $cart->user_id,
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

    $cart = (new Cart)->newFromBuilder($cart);

    $component = Volt::test('user.cart-item-list', ['cart' => $cart]);

    $component->assertSeeText($cart->items[0]->name)
        ->call('increment', $cart->items[0]->id);

    $this->assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'quantity' => 3,
    ]);
});

test('cart decrement button works', function () {
    $product = Product::factory()->create();

    $product = Product::queryBySlug(slug: $product->slug, columns: [
        'products.id',
        'products.subcategory_id',
        'products.name',
        'products.slug',
        'products.description',
        'products.main_sku',
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
        'reviews',
        'aggregates',
    ]);

    $product->images = collect($product->images);

    $product = (new Product)->newFromBuilder($product);

    $firstProductVariant = $product->variation->variants[0];
    $firstProductVariantPrice = $firstProductVariant->price_discount ? $firstProductVariant->price_discount : $firstProductVariant->price;

    $cart = $this->user->cart()->create();

    expect($cart)->not->toBeNull();

    $cart->items()->create([
        'product_variant_id' => $firstProductVariant->id,
        'price' => $firstProductVariantPrice * 2,
        'quantity' => 2,
    ]);

    expect($cart->items->pluck('product_variant_id'))->toContain($firstProductVariant->id);

    $response = $this->get('/keranjang-belanja');

    $response
        ->assertOk()
        ->assertSeeVolt('user.cart-item-list');

    $cart = Cart::queryByUserIdWithRelations(
        userId: $cart->user_id,
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

    $cart = (new Cart)->newFromBuilder($cart);

    $component = Volt::test('user.cart-item-list', ['cart' => $cart]);

    $component->assertSeeText($cart->items[0]->name)
        ->call('decrement', $cart->items[0]->id);

    $this->assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'quantity' => 1,
    ]);
});

test('cart quantity input works', function () {
    $product = Product::factory()->create();

    $product = Product::queryBySlug(slug: $product->slug, columns: [
        'products.id',
        'products.subcategory_id',
        'products.name',
        'products.slug',
        'products.description',
        'products.main_sku',
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
        'reviews',
        'aggregates',
    ]);

    $product->images = collect($product->images);

    $product = (new Product)->newFromBuilder($product);

    $firstProductVariant = $product->variation->variants[0];
    $firstProductVariantPrice = $firstProductVariant->price_discount ? $firstProductVariant->price_discount : $firstProductVariant->price;

    $cart = $this->user->cart()->create();

    expect($cart)->not->toBeNull();

    $cart->items()->create([
        'product_variant_id' => $firstProductVariant->id,
        'price' => $firstProductVariantPrice * 2,
        'quantity' => 1,
    ]);

    expect($cart->items->pluck('product_variant_id'))->toContain($firstProductVariant->id);

    $response = $this->get('/keranjang-belanja');

    $response
        ->assertOk()
        ->assertSeeVolt('user.cart-item-list');

    $cart = Cart::queryByUserIdWithRelations(
        userId: $cart->user_id,
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

    $cart = (new Cart)->newFromBuilder($cart);

    $component = Volt::test('user.cart-item-list', ['cart' => $cart]);

    $component->assertSeeText($cart->items[0]->name)
        ->call('updateItemQuantity', $cart->items[0]->id, 2);

    $this->assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'quantity' => 2,
    ]);
});

test('delete cart item works', function () {
    $product = Product::factory()->create();

    $product = Product::queryBySlug(slug: $product->slug, columns: [
        'products.id',
        'products.subcategory_id',
        'products.name',
        'products.slug',
        'products.description',
        'products.main_sku',
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
        'reviews',
        'aggregates',
    ]);

    $product->images = collect($product->images);

    $product = (new Product)->newFromBuilder($product);

    $firstProductVariant = $product->variation->variants[0];
    $firstProductVariantPrice = $firstProductVariant->price_discount ? $firstProductVariant->price_discount : $firstProductVariant->price;

    $cart = $this->user->cart()->create();

    expect($cart)->not->toBeNull();

    $cart->items()->create([
        'product_variant_id' => $firstProductVariant->id,
        'price' => $firstProductVariantPrice * 2,
        'quantity' => 1,
    ]);

    expect($cart->items->pluck('product_variant_id'))->toContain($firstProductVariant->id);

    $response = $this->get('/keranjang-belanja');

    $response
        ->assertOk()
        ->assertSeeVolt('user.cart-item-list');

    $cart = Cart::queryByUserIdWithRelations(
        userId: $cart->user_id,
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

    $cart = (new Cart)->newFromBuilder($cart);

    $component = Volt::test('user.cart-item-list', ['cart' => $cart]);

    $component->assertSeeText($cart->items[0]->name)
        ->call('delete', $cart->items[0]->id);

    $this->assertDatabaseMissing('cart_items', [
        'cart_id' => $cart->id,
    ]);
});
