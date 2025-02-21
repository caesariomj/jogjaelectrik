<?php

use App\Livewire\Actions\Logout;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public ?string $role = null;

    public string $search = '';

    public $searchResults = null;

    public function mount()
    {
        $this->role = auth()->check()
            ? auth()
                ->user()
                ->roles->first()->name
            : null;
    }

    /**
     * Get the user cart cart items and discount if applied.
     */
    #[Computed]
    public function cart()
    {
        if (! auth()->check()) {
            return;
        }

        if ($this->role !== 'user') {
            return;
        }

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
                'categories.slug as category_slug',
                'subcategories.id as subcategory_id',
                'subcategories.slug as subcategory_slug',
                'product_variants.variant_sku',
                'variation_variants.name as variant_name',
                'variations.name as variation_name',
                'product_images.file_name as thumbnail',
                'discounts.type as discount_type',
                'discounts.value as discount_value',
                'discounts.max_discount_amount as discount_max_discount_amount',
            ],
            relations: ['items', 'discount'],
        )->get();

        if ($cart->isEmpty()) {
            return;
        }

        if ($cart->first()->id === null) {
            $this->cart = null;
            return;
        }

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
            'total_weight' => (int) $totalWeight,
            'total_price' => (float) $totalPrice,
            'discount_amount' => (float) $this->calculateDiscount(
                $cartData->discount_type,
                $cartData->discount_value,
                $cartData->discount_max_discount_amount,
                $totalPrice,
            ),
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
                    'weight' => (float) $item->weight,
                    'variant' => $item->variant_name,
                    'variation' => $item->variation_name,
                ];
            }),
        ];

        if ($cart->items->first()->id === null) {
            $cart->items = null;
        }

        return (new Cart())->newFromBuilder($cart);
    }

    /**
     * Calculate discount if applied.
     *
     * @param   ?string  $type - The type of the discount to calculate.
     * @param   ?float  $value - The value of the discount to calculate.
     * @param   ?float  $maxDiscountAmount - The max discount amount of the discount to calculate.
     * @param   string  $totalPrice - The total price of the cart items to calculate.
     *
     * @return  float
     */
    private function calculateDiscount(
        ?string $type = null,
        ?float $value = null,
        ?float $maxDiscountAmount = null,
        float $totalPrice,
    ): float {
        if (! $type && ! $value) {
            return (float) 0.0;
        }

        if ($type === 'fixed') {
            return (float) min($value, $totalPrice);
        } elseif ($type === 'percentage') {
            $discountAmount = $totalPrice * ($value / 100);

            if ($maxDiscountAmount && $discountAmount > $maxDiscountAmount) {
                return (float) $maxDiscountAmount;
            } else {
                return (float) $discountAmount;
            }
        }
    }

    /**
     * Delete cart item data.
     *
     * @param   string  $cartItemId - The ID of the cart item to delete.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to delete the cart item.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function delete(string $cartItemId)
    {
        if (! $this->cart) {
            session()->flash('error', 'Keranjang belanja Anda kosong.');
            return $this->redirectIntended(url()->previous(), navigate: true);
        }

        $existingCartItem = $this->cart->items
            ->filter(function ($cartItem) use ($cartItemId) {
                return $cartItem->id === $cartItemId;
            })
            ->first();

        if (! $existingCartItem) {
            session()->flash('error', 'Produk tidak ditemukan pada keranjang belanja Anda.');
            return $this->redirectIntended(url()->previous(), navigate: true);
        }

        $existingCartItemName = $existingCartItem->name;

        $existingCartItem = (new CartItem())->newFromBuilder($existingCartItem);

        try {
            $this->authorize('delete', $this->cart);

            DB::transaction(function () use ($existingCartItem) {
                $existingCartItem->delete();
            });

            session()->flash(
                'success',
                'Produk ' . $existingCartItemName . ' berhasil dihapus dari keranjang belanja.',
            );
            return $this->redirectIntended(url()->previous(), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(url()->previous(), navigate: true);
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
                    'operation' => 'Deleting cart item data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menghapus produk ' .
                    $existingCartItemName .
                    ' dari keranjang belanja, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(url()->previous(), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Deleting cart item data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menghapus produk ' .
                    $existingCartItemName .
                    ' dari keranjang belanja, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(url()->previous(), navigate: true);
        }
    }

    /**
     * Search product.
     */
    public function updatedSearch()
    {
        $validated = $this->validate(
            rules: [
                'search' => 'nullable|string|min:1|max:255',
            ],
        );

        $this->search = trim(strip_tags($validated['search'] ?? ''));

        if (empty($this->search)) {
            $this->searchResults = collect();

            return;
        }

        $this->searchResults = Product::queryAllWithRelations(
            columns: [
                'products.id',
                'products.name',
                'products.slug',
                'products.base_price',
                'products.base_price_discount',
            ],
            relations: ['thumbnail', 'category', 'rating'],
        )
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->where(function ($query) {
                $query
                    ->where('products.name', 'like', '%' . $this->search . '%')
                    ->orWhere('subcategories.name', 'like', '%' . $this->search . '%')
                    ->orWhere('categories.name', 'like', '%' . $this->search . '%');
            })
            ->limit(8)
            ->get();
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<header x-data="{ isSearchBarOpen: false }" class="bg-white shadow-lg">
    <nav aria-label="Navigasi utama">
        <div
            class="container mx-auto flex h-14 max-w-md items-center justify-start gap-x-4 px-6 md:max-w-screen-2xl md:justify-center md:gap-x-8 md:px-12"
        >
            <div class="mr-auto h-full shrink md:w-96 lg:shrink-0">
                <div class="flex h-full items-center lg:hidden">
                    <button
                        type="button"
                        class="rounded-full bg-white p-2 text-black transition-colors hover:bg-neutral-200"
                        aria-label="Buka menu"
                        x-on:click.prevent.stop="$dispatch('open-offcanvas', 'responsive-menu-offcanvas')"
                    >
                        <svg
                            class="size-5 shrink-0"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <line x1="4" x2="20" y1="12" y2="12" />
                            <line x1="4" x2="20" y1="6" y2="6" />
                            <line x1="4" x2="20" y1="18" y2="18" />
                        </svg>
                    </button>
                    <template x-teleport="body">
                        <x-common.offcanvas name="responsive-menu-offcanvas" position="left">
                            <x-slot name="title">
                                <x-common.application-logo class="block h-8 w-auto fill-current text-primary" />
                            </x-slot>
                            <ul class="mb-8 flex flex-col gap-y-2 px-4">
                                <li>
                                    <x-user.side-link
                                        href="{{ route('home') }}"
                                        class="!bg-neutral-50 !text-base hover:!bg-primary-50"
                                        wire:navigate
                                    >
                                        <svg
                                            class="size-5 shrink-0"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
                                            <path
                                                d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
                                            />
                                        </svg>
                                        Beranda
                                    </x-user.side-link>
                                </li>
                                <li>
                                    <x-common.accordion
                                        class="gap-x-3 rounded-md bg-neutral-50 !px-4 !py-2 !font-medium transition-colors hover:bg-primary-50 hover:text-primary"
                                    >
                                        <x-slot name="title">
                                            <svg
                                                class="size-5 shrink-0"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
                                                />
                                                <path d="m3.3 7 8.7 5 8.7-5" />
                                                <path d="M12 22V12" />
                                            </svg>
                                            <p class="mr-auto text-base">Produk</p>
                                        </x-slot>
                                        <ul class="mt-2 flex flex-col gap-y-2 ps-8">
                                            @foreach ($primaryCategories as $category)
                                                <li>
                                                    <x-user.side-link
                                                        href="{{ route('products.category', ['category' => $category->slug]) }}"
                                                        class="!bg-neutral-50 !text-base"
                                                        wire:navigate
                                                    >
                                                        {{ ucwords($category->name) }}
                                                    </x-user.side-link>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </x-common.accordion>
                                </li>
                                <li>
                                    <x-user.side-link
                                        href="{{ route('products') }}?sort=diskon"
                                        class="!bg-neutral-50 !text-base hover:!bg-primary-50"
                                        wire:navigate
                                    >
                                        <svg
                                            class="size-5 shrink-0"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <circle cx="12" cy="12" r="10" />
                                            <path d="m15 9-6 6" />
                                            <path d="M9 9h.01" />
                                            <path d="M15 15h.01" />
                                        </svg>
                                        Diskon
                                    </x-user.side-link>
                                </li>
                                <li>
                                    <x-user.side-link
                                        href="{{ route('products') }}?sort=terlaris"
                                        class="!bg-neutral-50 !text-base hover:!bg-primary-50"
                                        wire:navigate
                                    >
                                        <svg
                                            class="size-5 shrink-0"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
                                            <polyline points="16 7 22 7 22 13" />
                                        </svg>
                                        Paling Banyak Dibeli
                                    </x-user.side-link>
                                </li>
                                <li>
                                    <x-user.side-link
                                        href="{{ route('products') }}?sort=terbaru"
                                        class="!bg-neutral-50 !text-base hover:!bg-primary-50"
                                        wire:navigate
                                    >
                                        <svg
                                            class="size-5 shrink-0"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"
                                            />
                                            <path d="M20 3v4" />
                                            <path d="M22 5h-4" />
                                            <path d="M4 17v2" />
                                            <path d="M5 18H3" />
                                        </svg>
                                        Produk Terbaru
                                    </x-user.side-link>
                                </li>
                                <li>
                                    <x-user.side-link
                                        href="{{ route('products') }}?sort=terbaru"
                                        class="!bg-neutral-50 !text-base hover:!bg-primary-50"
                                        wire:navigate
                                    >
                                        <svg
                                            class="size-5 shrink-0"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"
                                            />
                                        </svg>
                                        Kontak Kami
                                    </x-user.side-link>
                                </li>
                                <li>
                                    <x-user.side-link
                                        href="{{ route('products') }}?sort=terbaru"
                                        class="!bg-neutral-50 !text-base hover:!bg-primary-50"
                                        wire:navigate
                                    >
                                        <svg
                                            class="size-5 shrink-0"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7" />
                                            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
                                            <path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4" />
                                            <path d="M2 7h20" />
                                            <path
                                                d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"
                                            />
                                        </svg>
                                        Tentang Kami
                                    </x-user.side-link>
                                </li>
                                @auth
                                    <li class="mt-2 border-t border-t-neutral-300 pt-4">
                                        <x-user.side-link
                                            href="{{ in_array($role, ['admin', 'super_admin']) ? route('admin.profile') : route('profile') }}"
                                            class="!bg-neutral-50 !text-base hover:!bg-primary-50"
                                            wire:navigate
                                        >
                                            <svg
                                                class="size-5 shrink-0"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                aria-hidden="true"
                                            >
                                                <path d="M18 20a6 6 0 0 0-12 0" />
                                                <circle cx="12" cy="10" r="4" />
                                                <circle cx="12" cy="12" r="10" />
                                            </svg>
                                            Profil Saya
                                        </x-user.side-link>
                                    </li>

                                    @can('view own orders')
                                        <li>
                                            <x-user.side-link
                                                href="{{ route('orders.index') }}"
                                                class="!bg-neutral-50 !text-base hover:!bg-primary-50"
                                                wire:navigate
                                            >
                                                <svg
                                                    class="size-5 shrink-0"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"
                                                    />
                                                    <path d="M12 22V12" />
                                                    <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7" />
                                                    <path d="m7.5 4.27 9 5.15" />
                                                </svg>
                                                Pesanan Saya
                                            </x-user.side-link>
                                        </li>
                                    @endcan

                                    <li>
                                        <x-user.side-link
                                            href="{{ in_array($role, ['admin', 'super_admin']) ? route('admin.setting') : route('setting') }}"
                                            class="!bg-neutral-50 !text-base hover:!bg-primary-50"
                                            wire:navigate
                                        >
                                            <svg
                                                class="size-5 shrink-0"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                aria-hidden="true"
                                            >
                                                <path d="M2 21a8 8 0 0 1 10.434-7.62" />
                                                <circle cx="10" cy="8" r="5" />
                                                <circle cx="18" cy="18" r="3" />
                                                <path d="m19.5 14.3-.4.9" />
                                                <path d="m16.9 20.8-.4.9" />
                                                <path d="m21.7 19.5-.9-.4" />
                                                <path d="m15.2 16.9-.9-.4" />
                                                <path d="m21.7 16.5-.9.4" />
                                                <path d="m15.2 19.1-.9.4" />
                                                <path d="m19.5 21.7-.4-.9" />
                                                <path d="m16.9 15.2-.4-.9" />
                                            </svg>
                                            Pengaturan Akun
                                        </x-user.side-link>
                                    </li>
                                    <li>
                                        <button
                                            type="button"
                                            class="flex w-full items-center gap-x-3 rounded-lg bg-neutral-50 px-4 py-2 text-base font-medium tracking-tight text-red-500 transition-colors hover:bg-red-50 active:ring-2 active:ring-red-200"
                                            wire:click="logout"
                                        >
                                            <svg
                                                class="size-5 shrink-0"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                aria-hidden="true"
                                            >
                                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                                <polyline points="16 17 21 12 16 7" />
                                                <line x1="21" x2="9" y1="12" y2="12" />
                                            </svg>
                                            Keluar
                                        </button>
                                    </li>
                                @else
                                    <li class="mt-2 flex items-center gap-x-2 border-t border-t-neutral-300 pt-4">
                                        <x-common.button :href="route('login')" variant="secondary" wire:navigate>
                                            Masuk
                                        </x-common.button>
                                        <x-common.button :href="route('register')" variant="primary" wire:navigate>
                                            Daftar
                                        </x-common.button>
                                    </li>
                                @endauth
                            </ul>
                            <ul class="grid grid-cols-2 gap-x-4 px-4 pb-8">
                                <li>
                                    <h2
                                        class="mb-4 text-balance text-base font-semibold leading-tight tracking-tight text-black"
                                    >
                                        Bantuan dan Layanan Pelanggan
                                    </h2>
                                    <ul class="flex flex-col gap-y-2">
                                        <li>
                                            <a
                                                href="{{ route('faq') }}"
                                                class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                                                wire:navigate
                                            >
                                                FAQ
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                href="#"
                                                class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                                                wire:navigate
                                            >
                                                Cara Pemesanan
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                href="#"
                                                class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                                                wire:navigate
                                            >
                                                Kebijakan Pengiriman
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                href="#"
                                                class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                                                wire:navigate
                                            >
                                                Kebijakan Pengembalian Barang
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <h2
                                        class="mb-4 text-balance text-base font-semibold leading-tight tracking-tight text-black"
                                    >
                                        Legalitas dan Keamanan
                                    </h2>
                                    <ul class="flex flex-col gap-y-2">
                                        <li>
                                            <a
                                                href="#"
                                                class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                                                wire:navigate
                                            >
                                                Syarat dan Ketentuan
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                href="#"
                                                class="text-sm font-medium leading-tight tracking-tight text-black transition-colors hover:text-primary"
                                                wire:navigate
                                            >
                                                Kebijakan Privasi
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </x-common.offcanvas>
                    </template>
                </div>
                <ul class="hidden lg:flex lg:h-full lg:w-full lg:items-center lg:justify-start lg:gap-x-8">
                    @foreach ($primaryCategories as $category)
                        <li x-data="{ open: false }" class="h-full">
                            <button
                                type="button"
                                class="inline-flex h-full w-full items-center gap-x-2 text-nowrap text-sm font-semibold leading-none tracking-tight text-black transition-colors hover:text-primary"
                                x-on:click="open = !open"
                                x-on:click.away="open = false"
                            >
                                {{ ucwords($category->name) }}
                                <svg
                                    class="size-4 shrink-0 transition-transform"
                                    :class="{ 'rotate-180' : open }"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                class="container absolute start-0 top-full mx-auto w-full max-w-screen-2xl rounded-b-lg bg-white px-12 py-6 shadow-lg"
                                x-transition:enter="transition-opacity"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition-opacity"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                x-cloak
                                x-on:click.stop
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <ul class="grid w-2/3 grid-cols-3 gap-2">
                                        @foreach ($category->subcategories as $subcategory)
                                            <li>
                                                <a
                                                    href="{{ route('products.subcategory', ['category' => $category->slug, 'subcategory' => $subcategory->slug]) }}"
                                                    class="text-sm font-semibold leading-none tracking-tight text-black transition-colors hover:text-primary"
                                                    wire:navigate
                                                >
                                                    {{ ucwords($subcategory->name) }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <a
                                        href="{{ route('products.category', ['category' => $category->slug]) }}"
                                        class="h-96 w-1/3"
                                        wire:navigate
                                    >
                                        <figure
                                            class="relative h-full w-full overflow-hidden rounded-xl border shadow-xl"
                                        >
                                            <div
                                                class="absolute inset-0 z-[1] bg-gradient-to-t from-black to-transparent"
                                            ></div>
                                            <img
                                                src="https://penguinui.s3.amazonaws.com/component-assets/carousel/default-slide-1.webp"
                                                alt="Kategori {{ $category->name }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            />
                                            <figcaption class="absolute bottom-0 start-0 z-[2] p-8">
                                                <x-common.button variant="secondary">
                                                    Lihat {{ ucwords($category->name) }}
                                                    <svg
                                                        class="size-5 shrink-0"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        aria-hidden="true"
                                                    >
                                                        <path d="M18 8L22 12L18 16" />
                                                        <path d="M2 12H22" />
                                                    </svg>
                                                </x-common.button>
                                            </figcaption>
                                        </figure>
                                    </a>
                                </div>
                            </div>
                        </li>
                    @endforeach

                    <li>
                        <a
                            href="{{ route('products') . '?sort=diskon' }}"
                            class="text-sm font-semibold leading-none tracking-tight text-black transition-colors hover:text-primary"
                            wire:navigate
                        >
                            Diskon
                        </a>
                    </li>
                </ul>
            </div>
            <div class="-ms-16 mr-auto h-8 shrink-0 md:mx-0">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex h-full w-full items-center justify-center gap-x-4"
                    aria-label="Beranda"
                    wire:navigate
                >
                    <x-common.application-logo class="block h-full w-auto fill-current text-primary" />
                    <span class="hidden text-xl font-bold uppercase tracking-tight lg:block">
                        {{ config('app.name') }}
                    </span>
                </a>
            </div>
            <ul class="ml-auto flex shrink items-center justify-end gap-x-2 md:w-96 md:gap-x-4">
                <li>
                    <button
                        type="button"
                        class="rounded-full bg-white p-2 text-black transition-colors hover:bg-neutral-200"
                        aria-label="Pencarian produk"
                        x-on:click="isSearchBarOpen = true"
                    >
                        <svg
                            class="size-5 shrink-0"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                    </button>
                </li>
                @auth
                    @can('view own cart')
                        <li>
                            <button
                                type="button"
                                class="relative rounded-full bg-white p-2 text-black transition-colors hover:bg-neutral-200"
                                aria-label="Buka keranjang belanja"
                                x-on:click.prevent.stop="$dispatch('open-offcanvas', 'cart-offcanvas-summary')"
                            >
                                <svg
                                    class="size-5 shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="8" cy="21" r="1" />
                                    <circle cx="19" cy="21" r="1" />
                                    <path
                                        d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"
                                    />
                                </svg>
                                @if ($this->cart && $this->cart->items && $this->cart->items->count() > 0)
                                    <span
                                        class="absolute -end-1 -top-1 inline-flex size-5 items-center justify-center rounded-full border-2 border-white bg-red-500 p-0.5 text-xs font-semibold text-white"
                                        aria-live="polite"
                                    >
                                        {{ count($this->cart->items) > 9 ? '9+' : count($this->cart->items) }}
                                    </span>
                                @endif
                            </button>
                            <x-common.offcanvas name="cart-offcanvas-summary" wire:ignore>
                                <x-slot name="title">
                                    <h2 id="label-cart-offcanvas-summary" class="text-xl font-semibold">
                                        Keranjang Belanja
                                    </h2>
                                </x-slot>
                                <div class="flex h-[calc(100vh-6.5rem)] flex-col overflow-hidden">
                                    @if ($this->cart && $this->cart->items && $this->cart->items->count() > 0)
                                        <div class="flex-1 divide-y divide-neutral-300 overflow-y-auto">
                                            @foreach ($this->cart->items as $item)
                                                <article
                                                    wire:key="{{ $item->id }}"
                                                    class="flex items-start gap-x-4 p-4"
                                                >
                                                    <a
                                                        href="{{ route('products.detail', ['category' => $item->category_slug, 'subcategory' => $item->subcategory_slug, 'slug' => $item->slug]) }}"
                                                        class="size-28 overflow-hidden rounded-lg bg-neutral-100"
                                                        wire:navigate
                                                    >
                                                        <img
                                                            src="{{ asset('storage/uploads/product-images/' . $item->thumbnail) }}"
                                                            alt="Gambar produk {{ strtolower($item->name) }}"
                                                            class="aspect-square h-full w-full scale-100 object-cover brightness-100 transition-all ease-in-out hover:scale-105 hover:brightness-95"
                                                            loading="lazy"
                                                        />
                                                    </a>
                                                    <div class="flex flex-col items-start">
                                                        <a
                                                            href="{{ route('products.detail', ['category' => $item->category_slug, 'subcategory' => $item->subcategory_slug, 'slug' => $item->slug]) }}"
                                                            class="mb-0.5"
                                                            wire:navigate
                                                        >
                                                            <h3
                                                                class="!text-lg text-black transition-colors hover:text-primary"
                                                            >
                                                                {{ $item->name }}
                                                            </h3>
                                                        </a>

                                                        @if ($item->variation && $item->variant)
                                                            <p class="mb-1 text-sm tracking-tight text-black">
                                                                {{ ucwords($item->variation) . ': ' . ucwords($item->variant) }}
                                                            </p>
                                                        @endif

                                                        <p
                                                            class="inline-flex items-center text-sm font-medium tracking-tighter text-black/70"
                                                        >
                                                            <span class="me-2">{{ $item->quantity }}</span>
                                                            x
                                                            <span class="ms-2 tracking-tight text-black">
                                                                Rp {{ formatPrice($item->price) }}
                                                            </span>
                                                        </p>
                                                        <button
                                                            type="button"
                                                            class="mt-4 inline-flex items-center text-sm font-medium text-red-600 hover:underline"
                                                            wire:click="delete('{{ $item->id }}')"
                                                        >
                                                            <svg
                                                                class="me-2 size-5 shrink-0"
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                fill="currentColor"
                                                                viewBox="0 0 256 256"
                                                                aria-hidden="true"
                                                                wire:loading.remove
                                                                wire:target="delete('{{ $item->id }}')"
                                                            >
                                                                <path
                                                                    d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192ZM112,104v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Z"
                                                                />
                                                            </svg>
                                                            <div
                                                                class="me-2 inline-block size-4 shrink-0 animate-spin rounded-full border-[2px] border-current border-t-transparent align-middle"
                                                                role="status"
                                                                aria-label="Sedang diproses"
                                                                wire:loading
                                                                wire:target="delete('{{ $item->id }}')"
                                                            >
                                                                <span class="sr-only">Sedang diproses...</span>
                                                            </div>
                                                            <span
                                                                wire:loading.remove
                                                                wire:target="delete('{{ $item->id }}')"
                                                            >
                                                                Hapus
                                                            </span>
                                                            <span wire:loading wire:target="delete('{{ $item->id }}')">
                                                                Sedang diproses...
                                                            </span>
                                                        </button>
                                                    </div>
                                                </article>
                                            @endforeach
                                        </div>
                                        <div class="mt-auto space-y-2 border-t border-t-neutral-300 px-6 pt-6">
                                            <dl class="mb-2 grid grid-cols-2 gap-y-2">
                                                <dt
                                                    class="text-start text-base leading-none tracking-tight text-black/70"
                                                >
                                                    Subtotal:
                                                </dt>
                                                <dd
                                                    class="text-end text-base leading-none tracking-tight text-black/70"
                                                >
                                                    Rp {{ formatPrice($this->cart->total_price) }}
                                                </dd>
                                                <dt
                                                    class="text-start text-base leading-none tracking-tight text-black/70"
                                                >
                                                    Potongan Diskon:
                                                </dt>
                                                <dd
                                                    @class([
                                                        'text-end text-base font-medium leading-none tracking-tight',
                                                        'text-black/70' => $this->cart->discount_amount <= 0.0,
                                                        'text-teal-500' => $this->cart->discount_amount > 0.0,
                                                    ])
                                                >
                                                    - Rp
                                                    {{ $this->cart->discount_amount > 0 ? formatPrice($this->cart->discount_amount) : 0 }}
                                                </dd>
                                                <dt
                                                    class="text-start text-base font-semibold leading-none tracking-tight text-black"
                                                >
                                                    Total:
                                                </dt>
                                                <dd
                                                    class="text-end text-base font-semibold leading-none tracking-tight text-black"
                                                >
                                                    Rp
                                                    {{ $this->cart->discount_amount > 0 ? formatPrice($this->cart->total_price - $this->cart->discount_amount) : formatPrice($this->cart->total_price) }}
                                                </dd>
                                            </dl>
                                            <x-common.button
                                                :href="route('cart')"
                                                class="w-full"
                                                variant="secondary"
                                                wire:navigate
                                            >
                                                <svg
                                                    class="size-5 shrink-0"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <circle cx="8" cy="21" r="1" />
                                                    <circle cx="19" cy="21" r="1" />
                                                    <path
                                                        d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"
                                                    />
                                                </svg>
                                                Keranjang Belanja
                                            </x-common.button>
                                            <x-common.button
                                                :href="route('checkout')"
                                                class="w-full"
                                                variant="primary"
                                                wire:navigate
                                            >
                                                Checkout
                                                <svg
                                                    class="size-5 shrink-0"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M18 8L22 12L18 16" />
                                                    <path d="M2 12H22" />
                                                </svg>
                                            </x-common.button>
                                        </div>
                                    @else
                                        <figure class="flex h-full flex-col items-center justify-center">
                                            <img
                                                src="https://placehold.co/400"
                                                class="mb-6 size-72 object-cover"
                                                alt="Gambar ilustrasi keranjang kosong"
                                                loading="lazy"
                                            />
                                            <figcaption class="flex flex-col items-center">
                                                <h2 class="mb-3 text-center !text-2xl text-black">
                                                    Keranjang Belanja Anda Masih Kosong
                                                </h2>
                                                <p
                                                    class="mb-8 text-center text-base font-normal tracking-tight text-black/70"
                                                >
                                                    Seluruh produk yang Anda tambahkan ke dalam keranjang belanja akan
                                                    ditampilkan disini.
                                                </p>
                                                <x-common.button
                                                    :href="route('products')"
                                                    variant="primary"
                                                    wire:navigate
                                                >
                                                    Belanja Sekarang
                                                    <svg
                                                        class="size-5 shrink-0"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        fill="currentColor"
                                                        viewBox="0 0 256 256"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z"
                                                        />
                                                    </svg>
                                                </x-common.button>
                                            </figcaption>
                                        </figure>
                                    @endif
                                </div>
                            </x-common.offcanvas>
                        </li>
                    @endcan

                    <li>
                        <x-common.dropdown align="right" width="72">
                            <x-slot name="trigger">
                                <button
                                    type="button"
                                    class="relative mt-1.5 rounded-full bg-white p-2 text-black transition-colors hover:bg-neutral-200"
                                    aria-label="Buka notifikasi"
                                >
                                    <svg
                                        class="size-5 shrink-0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M10.268 21a2 2 0 0 0 3.464 0" />
                                        <path
                                            d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"
                                        />
                                    </svg>
                                    <span
                                        class="absolute -end-1 -top-1 inline-flex size-5 items-center justify-center rounded-full border-2 border-white bg-red-500 p-0.5 text-xs font-semibold text-white"
                                        aria-live="polite"
                                    >
                                        9
                                    </span>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div
                                    class="flex w-full flex-col items-start gap-y-1 overflow-hidden border-b border-b-neutral-300 p-4"
                                >
                                    <p class="text-sm font-semibold leading-none tracking-tight text-black">
                                        Notifikasi
                                    </p>
                                </div>
                                <ul class="h-full max-h-96 overflow-y-auto">
                                    @for ($i = 0; $i < 5; $i++)
                                        <li>
                                            <x-common.dropdown-link
                                                :href="route('home')"
                                                class="justify-between"
                                                wire:navigate
                                            >
                                                <div class="flex items-start gap-x-3">
                                                    <div class="size-10 shrink-0 rounded-md bg-teal-50 p-2.5">
                                                        <svg
                                                            class="h-full w-full shrink-0 text-teal-500"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            aria-hidden="true"
                                                        >
                                                            <path
                                                                d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"
                                                            />
                                                            <path d="M12 22V12" />
                                                            <polyline points="3.29 7 12 12 20.71 7" />
                                                            <path d="m7.5 4.27 9 5.15" />
                                                        </svg>
                                                    </div>
                                                    <div class="flex flex-col items-start gap-y-1.5">
                                                        <p
                                                            class="text-pretty text-sm font-medium leading-none tracking-tight text-black"
                                                        >
                                                            Header Notifikasi Yang Cukup Panjang Lorem ipsum dolor sit
                                                            amet.
                                                        </p>
                                                        <p
                                                            class="text-pretty text-sm font-normal leading-none tracking-tight text-black/70"
                                                        >
                                                            Paragraf notifikasi Lorem ipsum dolor sit amet consectetur,
                                                            adipisicing elit. Officia, exercitationem!
                                                        </p>
                                                    </div>
                                                </div>
                                                <span
                                                    class="inline-block size-2 shrink-0 rounded-full bg-red-500"
                                                ></span>
                                            </x-common.dropdown-link>
                                        </li>
                                    @endfor
                                </ul>
                                <div class="-mb-1 border-t border-t-neutral-300">
                                    <a
                                        href="{{ route('home') }}"
                                        class="flex w-full items-center justify-center bg-white p-4 text-sm font-medium leading-none tracking-tight text-black transition-colors hover:bg-primary-50 hover:text-primary"
                                        wire:navigate
                                    >
                                        Lihat Semua
                                    </a>
                                </div>
                            </x-slot>
                        </x-common.dropdown>
                    </li>
                    <li>
                        <x-common.dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button
                                    type="button"
                                    class="mt-1.5 rounded-full bg-white p-2 text-black transition-colors hover:bg-neutral-200"
                                    aria-label="Buka akun saya"
                                >
                                    <svg
                                        class="size-5 shrink-0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M18 20a6 6 0 0 0-12 0" />
                                        <circle cx="12" cy="10" r="4" />
                                        <circle cx="12" cy="12" r="10" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div
                                    class="flex w-full flex-col items-start gap-y-1 overflow-hidden border-b border-b-neutral-300 px-4 py-2"
                                >
                                    <p
                                        class="w-full truncate text-sm font-semibold leading-none tracking-tight text-black"
                                    >
                                        {{ auth()->user()->name }}
                                    </p>
                                    <p class="w-full truncate text-sm leading-none tracking-tight text-black/70">
                                        {{ auth()->user()->email }}
                                    </p>
                                </div>

                                @can('access admin page')
                                    <x-common.dropdown-link :href="route('admin.dashboard')" wire:navigate>
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
                                        >
                                            <path d="M15.6 2.7a10 10 0 1 0 5.7 5.7" />
                                            <circle cx="12" cy="12" r="2" />
                                            <path d="M13.4 10.6 19 5" />
                                        </svg>
                                        Dashboard Admin
                                    </x-common.dropdown-link>
                                @endcan

                                <x-common.dropdown-link
                                    :href="in_array($role, ['admin', 'super_admin']) ? route('admin.profile') : route('profile')"
                                    wire:navigate
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
                                    >
                                        <path d="M18 20a6 6 0 0 0-12 0" />
                                        <circle cx="12" cy="10" r="4" />
                                        <circle cx="12" cy="12" r="10" />
                                    </svg>
                                    Profil Saya
                                </x-common.dropdown-link>

                                @can('view own orders')
                                    <x-common.dropdown-link :href="route('orders.index')" wire:navigate>
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
                                        >
                                            <path
                                                d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"
                                            />
                                            <path d="M12 22V12" />
                                            <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7" />
                                            <path d="m7.5 4.27 9 5.15" />
                                        </svg>
                                        Pesanan Saya
                                    </x-common.dropdown-link>
                                @endcan

                                @can('view own account')
                                    <x-common.dropdown-link
                                        :href="in_array($role, ['admin', 'super_admin']) ? route('admin.setting') : route('setting')"
                                        wire:navigate
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
                                        >
                                            <path d="M2 21a8 8 0 0 1 10.434-7.62" />
                                            <circle cx="10" cy="8" r="5" />
                                            <circle cx="18" cy="18" r="3" />
                                            <path d="m19.5 14.3-.4.9" />
                                            <path d="m16.9 20.8-.4.9" />
                                            <path d="m21.7 19.5-.9-.4" />
                                            <path d="m15.2 16.9-.9-.4" />
                                            <path d="m21.7 16.5-.9.4" />
                                            <path d="m15.2 19.1-.9.4" />
                                            <path d="m19.5 21.7-.4-.9" />
                                            <path d="m16.9 15.2-.4-.9" />
                                        </svg>
                                        Pengaturan Akun
                                    </x-common.dropdown-link>
                                @endcan

                                <x-common.dropdown-link wire:click="logout" class="text-red-500 hover:bg-red-50">
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
                                    >
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                        <polyline points="16 17 21 12 16 7" />
                                        <line x1="21" x2="9" y1="12" y2="12" />
                                    </svg>
                                    Keluar
                                </x-common.dropdown-link>
                            </x-slot>
                        </x-common.dropdown>
                    </li>
                @else
                    <li>
                        <x-common.button :href="route('login')" variant="secondary" wire:navigate>
                            Masuk
                        </x-common.button>
                    </li>
                    <li class="hidden lg:-ms-2 lg:block">
                        <x-common.button :href="route('register')" variant="primary" wire:navigate>
                            Daftar
                        </x-common.button>
                    </li>
                @endauth
            </ul>
        </div>
    </nav>
    <div
        class="absolute top-0 w-full shadow-lg"
        x-show="isSearchBarOpen"
        x-data="{ search: $wire.entangle('search').live }"
        x-init="
            $watch('isSearchBarOpen', (value) => {
                if (value) $nextTick(() => $refs.productSearch.focus())
            })

            $watch('search', (value) => {
                if (value.length > 0) {
                    document.body.style.overflow = 'hidden'
                    document.querySelector('main').style.overflow = 'hidden'
                    document.getElementById('search-result-box').style.overflowY = 'scroll'
                } else {
                    document.body.style.overflow = ''
                    document.querySelector('main').style.overflow = ''
                }
            })
        "
        x-transition:enter="transition-transform ease-out"
        x-transition:enter-start="-translate-y-12"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition-transform ease-in"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="-translate-y-12"
        x-on:keydown.escape.window="isSearchBarOpen = false; search = ''"
        x-on:click.away="isSearchBarOpen = false; search = ''"
        x-cloak
    >
        <div
            class="container relative z-10 mx-auto flex h-14 w-full max-w-md items-center justify-between gap-x-4 bg-white px-6 md:max-w-screen-2xl md:gap-x-8 md:px-12"
        >
            <svg
                class="size-5"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
            </svg>
            <form action="{{ route('products') }}" method="GET" class="w-full">
                <label for="product-search" class="sr-only">Cari produk</label>
                <input
                    type="text"
                    id="product-search"
                    name="q"
                    class="w-full border-transparent text-base font-medium tracking-tight focus:border-transparent focus:ring-0"
                    placeholder="Cari produk..."
                    autocomplete="off"
                    x-model.debounce.500ms="search"
                    x-ref="productSearch"
                    x-on:keydown.enter="$event.target.form.submit"
                />
            </form>
            <button
                type="button"
                class="rounded-full p-2 text-black hover:bg-neutral-100"
                aria-label="Tutup pencarian"
                x-on:click="isSearchBarOpen = false; search = ''"
            >
                <svg
                    class="size-5"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
        <template x-if="search.length > 0">
            <div
                id="search-result-box"
                class="container relative z-0 mx-auto flex h-[calc(100vh-3rem)] w-full max-w-full flex-col gap-8 overflow-y-auto rounded-b-lg bg-white px-6 py-6 shadow-inner md:h-[calc(100vh-3rem)] md:min-h-96 md:max-w-screen-2xl md:flex-row md:gap-6 md:px-12"
                x-data="{ searchResultOpen: false }"
                x-init="
                    $nextTick(() => {
                        searchResultOpen = true
                    })
                "
                x-show="searchResultOpen"
                x-transition:enter="transition-transform ease-out"
                x-transition:enter-start="-translate-y-12 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition-transform ease-in"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="-translate-y-12 opacity-0"
                x-cloak
            >
                <div class="w-full md:w-1/4">
                    <h2 class="mb-4 text-pretty leading-none text-black">Paling banyak dicari</h2>
                    <ul class="flex flex-col gap-y-2">
                        @foreach ($primaryCategories as $category)
                            <li>
                                <a
                                    href="{{ route('products.category', ['category' => $category->slug]) }}"
                                    class="text-base font-semibold leading-none tracking-tight text-black transition-colors hover:text-primary"
                                    wire:navigate
                                >
                                    {{ ucwords($category->name) }}
                                </a>
                            </li>
                            @foreach ($category->subcategories->take(5) as $subcategory)
                                <li>
                                    <a
                                        href="{{ route('products.subcategory', ['category' => $category->slug, 'subcategory' => $subcategory->slug]) }}"
                                        class="text-base font-semibold leading-none tracking-tight text-black transition-colors hover:text-primary"
                                        wire:navigate
                                    >
                                        {{ ucwords($subcategory->name) }}
                                    </a>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
                <div class="relative w-full md:w-3/4">
                    <h2 class="mb-4 text-pretty leading-none text-black">Produk</h2>
                    @if ($search)
                        <div class="relative mb-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                            @forelse ($searchResults as $product)
                                <x-common.product-card
                                    :product="$product"
                                    wire:key="{{ $product->id }}"
                                    wire:loading.class="!pointer-events-none !cursor-not-allowed opacity-50"
                                    wire:target="search"
                                />
                            @empty
                                <p
                                    class="col-span-2 text-base leading-none tracking-tight text-black/70 md:col-span-4"
                                    wire:loading.class="opacity-50"
                                    wire:target="search"
                                >
                                    Produk dengan nama "
                                    <strong x-text="search" class="text-black"></strong>
                                    " tidak ditemukan.
                                </p>
                            @endforelse
                        </div>
                        <div class="flex justify-center py-4 md:justify-start">
                            <x-common.button
                                :href="route('products')"
                                variant="primary"
                                wire:navigate
                                wire:loading.class="!pointer-events-none !cursor-not-allowed opacity-50"
                                wire:target="search"
                            >
                                Lihat Semua Produk
                                <svg
                                    class="size-5 shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M18 8L22 12L18 16" />
                                    <path d="M2 12H22" />
                                </svg>
                            </x-common.button>
                        </div>
                    @endif

                    <div
                        class="absolute left-1/2 top-36 h-full -translate-x-1/2 md:top-40"
                        wire:loading
                        wire:target="search"
                    >
                        <div
                            class="inline-block size-10 animate-spin rounded-full border-4 border-current border-t-transparent text-primary"
                            role="status"
                            aria-label="loading"
                        >
                            <span class="sr-only">Sedang diproses...</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</header>
