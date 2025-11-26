<?php

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    public Product $product;

    public int $quantity = 1;

    #[Locked]
    public ?object $category = null;

    public ?object $subcategory = null;

    public ?object $variation = null;

    public ?string $selectedVariantId = null;

    public int $stock = 0;

    public string $price = '';

    public ?string $priceDiscount = null;

    public Collection $reviews;

    public int $totalReviews = 0;

    public float $averageRating = 0.0;

    public Collection $reviewCountByRating;

    public Collection $reviewPercentageByRating;

    public function mount(Product $product): void
    {
        $this->product = $product;

        $this->category = $product->category;
        $this->subcategory = $product->subcategory;

        $this->setProductVariation();

        $this->setProductReviews();
    }

    /**
     * Set product price, priceDiscount, stock, selectedVariantId, and variation.
     */
    private function setProductVariation(): void
    {
        if (! $this->product->variation) {
            $this->price = $this->product->base_price;
            $this->priceDiscount = $this->product->base_price_discount;
            $this->stock = $this->product->total_stock;
            $this->selectedVariantId = $this->product->variant_id;
            $this->variation = null;
        } else {
            $this->variation = (object) [
                'id' => $this->product->variation->id,
                'name' => $this->product->variation->name,
                'variants' => collect($this->product->variation->variants),
            ];

            $this->setMinPriceProductVariant();
        }
    }

    /**
     * Set product price, priceDiscount, stock, selectedVariantId, and variation by the most minimum product variant price.
     */
    private function setMinPriceProductVariant(): void
    {
        $minPriceVariant = $this->variation->variants
            ->filter(function ($variant) {
                return $variant->price === $this->product->base_price ||
                    ($variant->price_discount &&
                        $this->product->base_price_discount &&
                        $variant->price_discount === $this->product->base_price_discount);
            })
            ->first();

        $this->selectedVariantId = $minPriceVariant->id;
        $this->price = $minPriceVariant->price;
        $this->priceDiscount = $minPriceVariant->price_discount;
        $this->stock = $minPriceVariant->stock;
    }

    /**
     * Set product totalReviews, averageRating, reviews, reviewCountByRating, and reviewPercentageByRating.
     */
    private function setProductReviews(): void
    {
        $this->totalReviews = $this->product->total_reviews;
        $this->averageRating = $this->product->average_rating;

        $this->reviews = collect($this->product->reviews)
            ->sortByDesc('created_at')
            ->values();

        $this->reviews = $this->reviews->map(function ($review) {
            $review->created_at = Carbon::parse($review->created_at);

            return $review;
        });

        $this->reviewCountByRating = $this->reviews->groupBy('rating')->map->count();

        $this->reviewCountByRating = collect([5, 4, 3, 2, 1])->mapWithKeys(function ($rating) {
            return [$rating => $this->reviewCountByRating->get($rating, 0)];
        });

        $this->reviewPercentageByRating = $this->reviewCountByRating->map(function ($count) {
            return $this->totalReviews > 0 ? ($count / $this->totalReviews) * 100 : 0;
        });
    }

    /**
     * Validate and set selectedVariantId on input change.
     */
    public function updatedSelectedVariantId(string $id): void
    {
        if ($this->variation === null) {
            return;
        }

        $selectedVariant = $this->variation->variants
            ->filter(function ($variant) use ($id) {
                return $variant->id === $id;
            })
            ->first();

        if (! $selectedVariant || ! $selectedVariant->is_active || $selectedVariant->stock <= 0) {
            $this->addError(
                'selectedVariantId',
                'Varian produk yang dipilih tidak tersedia. Silakan pilih varian produk lain.',
            );

            $this->setMinPriceProductVariant();

            return;
        }

        $this->price = $selectedVariant->price;
        $this->priceDiscount = $selectedVariant->price_discount;
        $this->stock = $selectedVariant->stock;
        $this->quantity = $this->quantity > $this->stock ? $this->stock : $this->quantity;
    }

    /**
     * Increment product quantity before adding to cart.
     */
    public function increment(): void
    {
        if ($this->quantity < $this->stock) {
            $this->quantity++;
        }
    }

    /**
     * Update product quantity on input change before adding to cart.
     */
    public function updateItemQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            $this->addError('quantity', 'Jumlah produk tidak bisa kurang dari 1.');

            $this->quantity = 1;

            return;
        }

        if ($quantity > $this->stock) {
            $this->addError('quantity', 'Jumlah produk melebihi stok yang tersedia. Stok tersedia: ' . $this->stock);

            $this->quantity = $this->stock;

            return;
        }

        $this->quantity = $quantity;
    }

    /**
     * Decrement product quantity before adding to cart.
     */
    public function decrement(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    /**
     * Add to cart.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to delete the cart item.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function addToCart()
    {
        if (! auth()->check()) {
            session()->flash(
                'error',
                'Silakan masuk terlebih dahulu sebelum menambahkan produk ke dalam keranjang belanja.',
            );

            return $this->redirectRoute('login', navigate: true);
        }

        if (
            auth()
                ->user()
                ->roles->first()->name !== 'user'
        ) {
            session()->flash('error', 'Admin tidak dapat menambahkan produk di dalam keranjang belanja.');

            return $this->redirectIntended(url()->previous(), navigate: true);
        }

        $validated = $this->validate(
            rules: [
                'selectedVariantId' => 'required|string|uuid|exists:product_variants,id',
                'quantity' => 'required|integer|min:1|max:' . $this->stock,
            ],
            attributes: [
                'selectedVariantId' => 'Varian produk',
                'quantity' => 'Jumlah produk',
            ],
        );

        $cart = auth()
            ->user()
            ->cart()
            ->firstOrCreate();

        $existingCartItem = $cart
            ->items()
            ->where('product_variant_id', $validated['selectedVariantId'])
            ->first();

        $newQuantity = $existingCartItem
            ? $existingCartItem->quantity + $validated['quantity']
            : $validated['quantity'];

        if ($newQuantity > $this->stock) {
            $this->addError(
                'quantity',
                'Anda hanya bisa menambah ' .
                    $this->stock -
                    $existingCartItem->quantity .
                    ' produk ini lagi, karena sudah ada ' .
                    $existingCartItem->quantity .
                    ' produk ini di keranjang belanja anda. Maksimal stok: ' .
                    $this->stock,
            );

            return;
        }

        if ($this->variation && $this->variation->variants->count() > 1) {
            $selectedVariant = $this->variation->variants
                ->filter(function ($variant) use ($validated) {
                    return $variant->id === $validated['selectedVariantId'];
                })
                ->first();

            if (! $selectedVariant || ! $selectedVariant->is_active || $selectedVariant->stock <= 0) {
                $this->addError(
                    'selectedVariantId',
                    'Varian produk yang dipilih tidak tersedia. Silakan pilih varian lain yang tersedia.',
                );

                $this->setMinPriceProductVariant();

                return;
            }
        }

        try {
            $this->authorize('create', Cart::class);

            DB::transaction(function () use ($validated, $cart, $existingCartItem, $newQuantity) {
                if ($existingCartItem) {
                    $existingCartItem->update([
                        'quantity' => $newQuantity,
                    ]);
                } else {
                    $cart->items()->create([
                        'product_variant_id' => $validated['selectedVariantId'],
                        'quantity' => $validated['quantity'],
                        'price' => $this->priceDiscount ? $this->priceDiscount : $this->price,
                    ]);
                }
            });

            session()->flash(
                'success',
                'Produk ' . ucwords($this->product->name) . ' berhasil ditambahkan ke dalam keranjang belanja.',
            );
            return $this->redirectIntended(url()->previous(), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirect(url()->previous(), true);
        } catch (QueryException $e) {
            Log::error('Database query error occurred', [
                'error_type' => 'QueryException',
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Adding product inside cart item data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session(
                'error',
                'Terjadi kesalahan dalam menambahkan produk ' .
                    ucwords($this->product->name) .
                    ' ke dalam keranjang belanja, silakan coba beberapa saat lagi.',
            );
            return $this->redirect(url()->previous(), true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Adding product inside cart item data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session('error', 'Terjadi kesalahan tak terduga, silakan coba beberapa saat lagi.');
            return $this->redirect(url()->previous(), true);
        }
    }
}; ?>

<section class="p-4 md:p-6">
    <section class="flex flex-col gap-6 lg:flex-row">
        <x-common.product-image-gallery :images="$product->images" :productName="$product->name" />
        <section class="w-full lg:w-1/2">
            @if ($category && $subcategory)
                <nav>
                    <ol class="flex items-center">
                        <li>
                            <a
                                href="{{ route('products.category', ['category' => $category->slug]) }}"
                                class="text-sm font-medium tracking-tight text-black/70 transition-colors hover:text-black"
                                wire:navigate
                            >
                                {{ ucwords($category->name) }}
                            </a>
                        </li>
                        <li aria-hidden="true" class="mx-2 text-black/40">/</li>
                        <li>
                            <a
                                href="{{ route('products.subcategory', ['category' => $category->slug, 'subcategory' => $subcategory->slug]) }}"
                                class="text-sm font-medium tracking-tight text-black/70 transition-colors hover:text-black"
                                wire:navigate
                            >
                                {{ ucwords($subcategory->name) }}
                            </a>
                        </li>
                    </ol>
                </nav>
            @endif

            <h1 class="mb-2 leading-tight text-black">{{ $product->name }}</h1>
            <div class="mb-4 flex items-center gap-x-2">
                <x-common.product-rating :averageRating="$averageRating" />
                <p class="ml-2 text-sm font-medium tracking-tighter text-black/70">
                    <span class="mr-1">{{ $totalReviews }}</span>
                    penilaian
                </p>
            </div>
            <p class="mb-4 inline-flex items-center gap-4">
                @if ($priceDiscount)
                    <data value="{{ $priceDiscount }}" class="text-3xl font-bold tracking-tighter text-primary">
                        Rp {{ formatPrice($priceDiscount) }}
                    </data>
                    <del class="text-base tracking-tighter text-black/70">Rp {{ formatPrice($price) }}</del>
                @else
                    <data value="{{ $price }}" class="text-3xl font-bold tracking-tighter text-primary">
                        Rp {{ formatPrice($price) }}
                    </data>
                @endif
            </p>

            @if ($variation && $variation->variants->count() > 1)
                <hr class="my-4 border-neutral-300" />
                <div class="mb-4">
                    <p class="mb-4 text-base font-medium tracking-tight text-black">
                        Pilih Variasi
                        {{ ucwords($variation->name) . ' :' }}
                    </p>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($variation->variants as $variant)
                            <li wire:key="{{ $variant->id }}">
                                <input
                                    wire:model.lazy="selectedVariantId"
                                    type="radio"
                                    id="variant-{{ strtolower($variant->name) }}"
                                    name="product-variant"
                                    class="peer hidden"
                                    value="{{ $variant->id }}"
                                    @checked(! empty($this->selectedVariantId) && $this->selectedVariantId == $variant->id)
                                    @disabled(! $variant->is_active || $variant->stock <= 0)
                                />
                                <label
                                    for="variant-{{ strtolower($variant->name) }}"
                                    class="inline-flex min-w-28 cursor-pointer items-center justify-center gap-x-2 rounded-full border border-black bg-white px-4 py-3 text-sm font-semibold tracking-tight text-black transition-colors hover:bg-neutral-200 focus:bg-neutral-200 focus:outline-none disabled:pointer-events-none disabled:opacity-50 peer-checked:border-black peer-checked:bg-black peer-checked:text-white peer-disabled:cursor-not-allowed peer-disabled:border-black peer-disabled:bg-white peer-disabled:text-black peer-disabled:opacity-50"
                                    wire:loading.class="opacity-50 !cursor-wait"
                                    wire:target="selectedVariantId"
                                >
                                    {{ ucwords($variant->name) }}
                                </label>
                            </li>
                        @endforeach
                    </ul>
                    <x-form.input-error :messages="$errors->get('selectedVariantId')" class="mt-2" />
                </div>
            @endif

            <hr class="mt-4 border-neutral-300" />
            <div class="w-full divide-y divide-neutral-300">
                <x-common.accordion expanded>
                    <x-slot name="title">
                        <h3 class="text-lg tracking-tight text-black lg:text-xl">Spesifikasi Produk</h3>
                    </x-slot>
                    <dl class="grid grid-cols-2 gap-2 pb-4">
                        <dt class="text-pretty text-sm font-medium tracking-tight text-black/70 lg:text-base">Stok:</dt>
                        <dl
                            class="text-pretty text-sm font-medium tracking-tight text-black lg:text-base"
                            wire:loading.remove
                            wire:target="selectedVariantId"
                        >
                            @if ($this->stock > 0)
                                <span class="text-teal-600">• Masih Tersedia</span>
                                - Tersisa {{ $this->stock }}
                            @else
                                <span class="text-red-600">• Habis</span>
                            @endif
                        </dl>
                        <dl
                            class="text-pretty text-sm font-medium tracking-tight text-black/70 lg:text-base"
                            wire:loading
                            wire:target="selectedVariantId"
                        >
                            Sedang dimuat...
                        </dl>
                        <dt class="text-pretty text-sm font-medium tracking-tight text-black/70 lg:text-base">
                            Garansi:
                        </dt>
                        <dl class="text-pretty text-sm font-medium tracking-tight text-black lg:text-base">
                            {{ $product->warranty }}
                        </dl>
                        <dt class="text-pretty text-sm font-medium tracking-tight text-black/70 lg:text-base">
                            Bahan Material:
                        </dt>
                        <dl class="text-pretty text-sm font-medium tracking-tight text-black lg:text-base">
                            {{ $product->material }}
                        </dl>
                        <dt class="text-pretty text-sm font-medium tracking-tight text-black/70 lg:text-base">
                            Dimensi (panjang x lebar x tinggi):
                        </dt>
                        <dl class="text-pretty text-sm font-medium tracking-tight text-black lg:text-base">
                            {{ $product->dimension }} (dalam satuan centimeter)
                        </dl>
                        <dt class="text-pretty text-sm font-medium tracking-tight text-black/70 lg:text-base">
                            Berat Paket:
                        </dt>
                        <dl class="text-pretty text-sm font-medium tracking-tight text-black lg:text-base">
                            {{ formatPrice($product->weight) }} gram
                            {{ '(' . number_format($product->weight / 1000, 2) . ' kg)' }}
                        </dl>

                        @if ($product->power && $product->voltage)
                            <dt class="text-pretty text-sm font-medium tracking-tight text-black/70 lg:text-base">
                                Daya Listrik:
                            </dt>
                            <dl class="text-pretty text-sm font-medium tracking-tight text-black lg:text-base">
                                {{ $product->power }} W
                            </dl>
                            <dt class="text-pretty text-sm font-medium tracking-tight text-black/70 lg:text-base">
                                Tegangan Listrik:
                            </dt>
                            <dl class="text-pretty text-sm font-medium tracking-tight text-black lg:text-base">
                                {{ $product->voltage }} V
                            </dl>
                        @endif

                        <dt class="text-pretty text-sm font-medium tracking-tight text-black/70 lg:text-base">
                            Apa Yang Ada Di dalam Paket:
                        </dt>
                        <dl class="text-pretty text-sm font-medium tracking-tight text-black lg:text-base">
                            {{ $product->package }}
                        </dl>
                    </dl>
                </x-common.accordion>
                <x-common.accordion expanded>
                    <x-slot name="title">
                        <h3 class="text-lg tracking-tight text-black lg:text-xl">Deskripsi Produk</h3>
                    </x-slot>
                    <p
                        class="-mt-6 whitespace-pre-line text-pretty pb-4 text-sm font-medium tracking-tight text-black lg:text-base"
                    >
                        {{ $product->description }}
                    </p>
                </x-common.accordion>
            </div>
            @if ($this->stock > 0 &&(! auth()->check() ||(auth()->check() &&auth()->user()->roles->first()->name === 'user')))
                <div
                    class="sticky bottom-0 flex flex-col gap-y-4 border-y border-y-neutral-300 bg-white py-4 lg:border-b-0 lg:border-b-transparent"
                >
                    <div class="flex flex-col">
                        <div class="flex flex-row items-center justify-between">
                            <x-form.input-label for="quantity" value="Jumlah:" :required="false" />
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="flex size-11 items-center justify-center rounded-md border border-neutral-300 p-2 text-black transition-colors hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    aria-label="Kurangi jumlah produk"
                                    wire:click="decrement"
                                    wire:loading.class="!cursor-wait pointers-event-none opacity-50 hover:!bg-white"
                                    wire:target="selectedVariantId,increment,decrement,addToCart"
                                    @disabled($quantity <= 1)
                                >
                                    <svg
                                        class="size-4 shrink-0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                        wire:loading.remove
                                        wire:target="decrement"
                                    >
                                        <path d="M5 12h14" />
                                    </svg>
                                    <div
                                        class="inline-block size-4 animate-spin rounded-full border-[2px] border-current border-t-transparent align-middle"
                                        role="status"
                                        aria-label="loading"
                                        wire:loading
                                        wire:target="decrement"
                                    >
                                        <span class="sr-only">Sedang diproses...</span>
                                    </div>
                                </button>
                                <x-form.input
                                    wire:model.lazy="quantity"
                                    class="w-14 text-center text-black [appearance:textfield] disabled:cursor-not-allowed disabled:opacity-50 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                    type="number"
                                    name="quantity"
                                    id="quantity"
                                    inputmode="numeric"
                                    min="1"
                                    max="{{ $stock }}"
                                    autofocus
                                    :hasError="$errors->has('quantity')"
                                    wire:loading.class="!cursor-wait pointers-event-none opacity-50 hover:!bg-white"
                                    wire:target="selectedVariantId,increment,decrement,addToCart"
                                    x-on:change="$wire.updateItemQuantity($event.target.value)"
                                />
                                <button
                                    type="button"
                                    class="flex size-11 items-center justify-center rounded-md border border-neutral-300 p-2 text-black transition-colors hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-50"
                                    aria-label="Tambah jumlah produk"
                                    wire:click="increment"
                                    wire:loading.class="disabled"
                                    wire:target="selectedVariantId,increment,decrement,addToCart"
                                    @disabled($quantity >= $stock)
                                >
                                    <svg
                                        class="size-4 shrink-0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                        wire:loading.remove
                                        wire:target="increment"
                                    >
                                        <path d="M5 12h14" />
                                        <path d="M12 5v14" />
                                    </svg>
                                    <div
                                        class="inline-block size-4 animate-spin rounded-full border-[2px] border-current border-t-transparent align-middle"
                                        role="status"
                                        aria-label="loading"
                                        wire:loading
                                        wire:target="increment"
                                    >
                                        <span class="sr-only">Sedang diproses...</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                        <x-form.input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>
                    @auth
                        <x-common.button
                            type="button"
                            class="w-full !text-base lg:!py-4"
                            wire:click="addToCart"
                            wire:loading.attr="disabled"
                            wire:target="selectedVariantId,increment,decrement,addToCart"
                        >
                            <svg
                                class="size-6 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                                wire:loading.remove
                                wire:target="addToCart"
                            >
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                <path d="M3 6h18" />
                                <path d="M16 10a4 4 0 0 1-8 0" />
                            </svg>
                            <span wire:loading.remove wire:target="addToCart">Tambah ke Keranjang</span>
                            <div
                                class="inline-block size-6 animate-spin rounded-full border-[4px] border-current border-t-transparent align-middle"
                                role="status"
                                aria-label="loading"
                                wire:loading
                                wire:target="addToCart"
                            >
                                <span class="sr-only">Sedang diproses...</span>
                            </div>
                            <span wire:loading wire:target="addToCart">Sedang diproses...</span>
                        </x-common.button>
                    @else
                        <x-common.button :href="route('login')" class="w-full !text-base lg:!py-4" wire:navigate>
                            <svg
                                class="size-6 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                <path d="M3 6h18" />
                                <path d="M16 10a4 4 0 0 1-8 0" />
                            </svg>
                            Tambah ke Keranjang
                        </x-common.button>
                    @endauth
                    <x-common.button
                        href="{{ config('business.whatsapp') }}?text=Halo%2C%20apakah%20produk%20%2A{{ urlencode($product->name) }}%2A%20ini%20masih%20ada%3F%0A{{ $product->category_slug && $product->subcategory_slug ? route('products.detail', ['category' => $product->category_slug, 'subcategory' => $product->subcategory_slug, 'slug' => $product->slug]) : route('products.detail.without.category.subcategory', ['slug' => $product->slug]) }}"
                        variant="secondary"
                        class="w-full !text-base lg:!py-4"
                        target="_blank"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            stroke="none"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="size-6 shrink-0"
                            aria-hidden="true"
                        >
                            <path
                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"
                            />
                        </svg>
                        Tanyakan Stok
                    </x-common.button>
                </div>
            @endif
        </section>
    </section>
    <section class="mt-6">
        <h3 class="mb-4 text-lg font-semibold tracking-tight text-black lg:text-xl">Penilaian dan Ulasan Produk</h3>
        <section class="flex flex-col-reverse gap-6 lg:flex-row">
            <section class="w-full lg:w-3/4">
                @forelse ($reviews as $review)
                    <article
                        wire:key="{{ $review->id }}"
                        class="flex flex-row items-start gap-x-2 border-b border-neutral-300 py-4"
                    >
                        <svg
                            class="size-10 shrink-0 text-black opacity-20"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <div class="w-full">
                            <div class="flex flex-row items-center justify-between gap-x-2">
                                <p class="text-sm font-medium tracking-tight text-black">{{ $review->user_name }}</p>
                                <time
                                    class="text-sm font-medium tracking-tight text-black/70"
                                    datetime="2024-11-24 20:00"
                                >
                                    {{ $review->created_at->diffForHumans() }}
                                </time>
                            </div>
                            <div class="mt-1 inline-flex items-center gap-x-0.5" aria-labelledby="product-rating">
                                @for ($i = 0; $i < $review->rating; $i++)
                                    <svg
                                        class="size-3 text-yellow-500"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                                        />
                                    </svg>
                                @endfor

                                @for ($i = 0 + $review->rating; $i < 5; $i++)
                                    <svg
                                        class="size-3 text-black opacity-20"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                                        />
                                    </svg>
                                @endfor

                                <p class="ml-2 text-sm tracking-tighter text-black/70">({{ $review->rating }})</p>
                                <span class="sr-only">Penilaian: {{ $review->rating }} dari 5 bintang</span>
                            </div>

                            @if ($review->review)
                                <div class="mt-4">
                                    <p class="text-sm tracking-tight text-black">
                                        {{ $review->review }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="py-4 text-base font-medium tracking-tight text-black">
                        Belum ada penilaian dan ulasan untuk produk ini.
                    </p>
                @endforelse
            </section>
            <aside class="relative h-full w-full lg:sticky lg:top-20 lg:w-1/4">
                <div class="flex items-center gap-x-1">
                    <x-common.product-rating :averageRating="$averageRating" />
                    <p class="ml-auto text-xl font-semibold tracking-tighter text-black">
                        {{ $averageRating }}
                    </p>
                    <span class="sr-only">Penilaian: {{ $averageRating }} dari 5 bintang</span>
                </div>
                <hr class="my-4 border-neutral-300" />
                <div class="flex flex-col gap-y-1">
                    @foreach ($reviewCountByRating as $rating => $count)
                        <div class="flex items-center gap-x-2">
                            <span class="w-8 text-center text-base font-medium tracking-tighter text-black/70">
                                {{ $rating }}
                            </span>
                            <div
                                class="h-2 flex-grow overflow-hidden rounded-full bg-black/10"
                                role="progressbar"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ round($reviewPercentageByRating[$rating]) }}"
                            >
                                <div
                                    class="h-full rounded-full bg-primary"
                                    style="width: {{ round($reviewPercentageByRating[$rating]) }}%"
                                ></div>
                            </div>
                            <span class="w-8 text-end text-base font-medium tracking-tighter text-black">
                                {{ $count }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </aside>
        </section>
    </section>
</section>
