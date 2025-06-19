<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Discount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public ?Cart $cart = null;

    public ?Discount $discount = null;

    #[Locked]
    public ?Collection $items = null;

    #[Locked]
    public float $totalPrice = 0;

    #[Locked]
    public float $totalWeight = 0;

    #[Locked]
    public float $discountAmount = 0;

    public ?string $discountCode = null;

    public function mount(?Cart $cart = null)
    {
        $this->cart = $cart;

        if ($this->cart) {
            $this->items = $this->cart->items;

            $this->totalPrice = $this->cart->total_price ? $this->cart->total_price : 0;

            $this->totalWeight = $this->cart->total_weight ? $this->cart->total_weight : 0;

            if ($this->cart->discount) {
                $this->discount = $this->cart->discount;

                $this->discountCode = $this->cart->discount->code;

                $this->calculateDiscount();
            }
        }
    }

    /**
     * Calculate the total price and total weight.
     */
    private function calculateTotal(): void
    {
        $this->totalPrice = $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $this->totalWeight = $this->items->sum(function ($item) {
            return $item->weight * $item->quantity;
        });
    }

    /**
     * Calculate the discount applied.
     */
    private function calculateDiscount(): void
    {
        if ($this->discount->type === 'fixed') {
            $this->discountAmount = (float) min($this->discount->value, $this->totalPrice);
        } elseif ($this->discount->type === 'percentage') {
            $discountAmount = $this->totalPrice * ($this->discount->value / 100);

            if ($this->discount->max_discount_amount && $discountAmount > $this->discount->max_discount_amount) {
                $this->discountAmount = (float) $this->discount->max_discount_amount;
            } else {
                $this->discountAmount = (float) $discountAmount;
            }
        } else {
            $this->discountAmount = (float) 0.0;
        }
    }

    /**
     * Update cart item collection after changing the quantity.
     */
    private function updateItemCollection(string $cartItemId, int $newQuantity): void
    {
        $this->items = $this->items->map(function ($item) use ($cartItemId, $newQuantity) {
            if ($item->id === $cartItemId) {
                $item->quantity = $newQuantity;
            }

            return $item;
        });

        $this->calculateTotal();

        if ($this->discount) {
            $this->calculateDiscount();
        }
    }

    /**
     * Get list of discounts.
     */
    #[Computed]
    public function discounts()
    {
        $discounts = Discount::queryAllUsable(
            userId: auth()->id(),
            columns: [
                'id',
                'name',
                'description',
                'code',
                'type',
                'value',
                'max_discount_amount',
                'start_date',
                'end_date',
                'minimum_purchase',
            ],
        )
            ->get()
            ->map(function ($discount) {
                return (object) [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'description' => $discount->description,
                    'code' => $discount->code,
                    'type' => $discount->type,
                    'value' => $discount->value,
                    'max_discount_amount' => $discount->max_discount_amount,
                    'start_date' => $discount->start_date,
                    'end_date' => $discount->end_date,
                    'minimum_purchase' => $discount->minimum_purchase,
                    'is_eligible' => $this->totalPrice > $discount->minimum_purchase,
                ];
            });

        return $discounts;
    }

    /**
     * Increment cart item quantity.
     *
     * @param   string  $cartItemId - The ID of the cart item to increment.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to increment the cart item quantity.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function increment(string $cartItemId)
    {
        $existingCartItem = $this->items
            ->filter(function ($cartItem) use ($cartItemId) {
                return $cartItem->id === $cartItemId;
            })
            ->first();

        if (! $existingCartItem) {
            return;
        }

        $newQuantity = $existingCartItem->quantity + 1;

        if ($newQuantity > $existingCartItem->stock) {
            $this->addError(
                'quantity-' . $cartItemId,
                'Jumlah produk melebihi stok yang tersedia. Stok tersedia:' . $existingCartItem->stock,
            );
            return;
        }

        $existingCartItemName = $existingCartItem->name;

        $existingCartItem = (new CartItem())->newFromBuilder($existingCartItem);

        try {
            $this->authorize('update', $this->cart);

            DB::transaction(function () use ($existingCartItem, $newQuantity) {
                $existingCartItem->update([
                    'quantity' => $newQuantity,
                ]);
            });

            $this->updateItemCollection(cartItemId: $existingCartItem->id, newQuantity: $newQuantity);

            return;
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('cart'), navigate: true);
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
                    'operation' => 'Incrementing cart item data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menambahkan jumlah produk ' .
                    $existingCartItemName .
                    ' dari keranjang belanja, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('cart'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Incrementing cart item data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('cart'), navigate: true);
        }
    }

    /**
     * Update cart item quantity.
     *
     * @event   update-item-quantity - Listen to events fired at changes in input quantity.
     *
     * @param   string  $cartItemId - The ID of the cart item to update.
     * @param   int  $newQuantity - The new quantity of the cart item to update.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to update the cart item quantity.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    #[On('update-item-quantity')]
    public function updateItemQuantity(string $cartItemId, int $newQuantity)
    {
        $existingCartItem = $this->items
            ->filter(function ($cartItem) use ($cartItemId) {
                return $cartItem->id === $cartItemId;
            })
            ->first();

        if (! $existingCartItem) {
            return;
        }

        if ($newQuantity < 1) {
            $this->addError('quantity-' . $cartItemId, 'Jumlah produk tidak bisa kurang dari 1.');
            return;
        }

        if ($newQuantity > $existingCartItem->stock) {
            $this->addError(
                'quantity-' . $cartItemId,
                'Jumlah produk melebihi stok yang tersedia. Stok tersedia:' . $existingCartItem->stock,
            );
            return;
        }

        $newQuantity = max(1, min($newQuantity, $existingCartItem->stock));

        $existingCartItemName = $existingCartItem->name;

        $existingCartItem = (new CartItem())->newFromBuilder($existingCartItem);

        try {
            $this->authorize('update', $this->cart);

            DB::transaction(function () use ($existingCartItem, $newQuantity) {
                $existingCartItem->update([
                    'quantity' => $newQuantity,
                ]);
            });

            $this->updateItemCollection(cartItemId: $existingCartItem->id, newQuantity: $newQuantity);

            return;
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('cart'), navigate: true);
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
                    'operation' => 'Updating cart item quantity data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah jumlah produk ' .
                    $existingCartItemName .
                    ' dari keranjang belanja, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('cart'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Updating cart item quantity data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('cart'), navigate: true);
        }
    }

    /**
     * Decrement cart item quantity.
     *
     * @param   string  $cartItemId - The ID of the cart item to decrement.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to decrement the cart item.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function decrement(string $cartItemId)
    {
        $existingCartItem = $this->items
            ->filter(function ($cartItem) use ($cartItemId) {
                return $cartItem->id === $cartItemId;
            })
            ->first();

        if (! $existingCartItem) {
            return;
        }

        $newQuantity = $existingCartItem->quantity - 1;

        if ($newQuantity < 1) {
            $this->addError('quantity-' . $cartItemId, 'Jumlah produk tidak bisa kurang dari 1.');
            return;
        }

        $existingCartItemName = $existingCartItem->name;

        $existingCartItem = (new CartItem())->newFromBuilder($existingCartItem);

        try {
            $this->authorize('update', $this->cart);

            DB::transaction(function () use ($existingCartItem, $newQuantity) {
                $existingCartItem->update([
                    'quantity' => $newQuantity,
                ]);
            });

            $this->updateItemCollection(cartItemId: $existingCartItem->id, newQuantity: $newQuantity);

            return;
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('cart'), navigate: true);
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
                    'operation' => 'Decrementing cart item data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengurangi jumlah produk ' .
                    $existingCartItemName .
                    ' dari keranjang belanja, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('cart'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Decrementing cart item data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('cart'), navigate: true);
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
            return $this->redirectIntended(route('cart'), navigate: true);
        }

        $existingCartItem = $this->items
            ->filter(function ($cartItem) use ($cartItemId) {
                return $cartItem->id === $cartItemId;
            })
            ->first();

        if (! $existingCartItem) {
            session()->flash('error', 'Produk tidak ditemukan pada keranjang belanja Anda.');
            return $this->redirectIntended(route('cart'), navigate: true);
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
            return $this->redirectIntended(route('cart'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('cart'), navigate: true);
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
            return $this->redirectIntended(route('cart'), navigate: true);
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

            session()->flash('error', 'Terjadi kesalahan tak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('cart'), navigate: true);
        }
    }

    /**
     * Update cart item quantity.
     *
     * @event   update-item-quantity - Listen to events fired at changes in input quantity.
     *
     * @param   string  $cartItemId - The ID of the cart item to update.
     * @param   int  $newQuantity - The new quantity of the cart item to update.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to update the cart item quantity.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function updatedDiscountCode()
    {
        $this->validate(
            rules: [
                'discountCode' => 'nullable|string|alpha_dash:ascii|exists:discounts,code',
            ],
            attributes: [
                'discountCode' => 'Diskon',
            ],
        );

        $discount = Discount::queryAllUsable(
            userId: auth()->id(),
            columns: ['id', 'name', 'code', 'type', 'value', 'max_discount_amount', 'minimum_purchase'],
        )
            ->where('code', $this->discountCode)
            ->first();

        if (! $discount) {
            $this->addError('discountCode', 'Kode diskon tidak valid atau telah kedaluwarsa.');
            return;
        }

        if (! is_null($discount->minimum_purchase) && $this->totalPrice < $discount->minimum_purchase) {
            $this->discountCode = null;

            $this->addError(
                'discountCode',
                'Diskon hanya berlaku untuk pembelian minimal Rp ' . formatPrice($discount->minimum_purchase),
            );
            return;
        }

        $discountName = $discount->name;

        try {
            $this->authorize('applyDiscount', $this->cart);

            DB::transaction(function () use ($discount) {
                $this->cart->update([
                    'discount_id' => $discount->id,
                ]);
            });

            $this->discount = (new Discount())->newFromBuilder($discount);
            $this->discountCode = $this->discount->code;

            $this->calculateDiscount();

            session()->flash('success', 'Diskon ' . $discountName . ' berhasil diterapkan.');
            return $this->redirectIntended(route('cart'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('cart'), navigate: true);
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
                    'operation' => 'Using discount data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan pada saat menerapkan diskon ' . $discountName . ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('cart'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Using discount data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('cart'), navigate: true);
        }
    }

    public function cancelDiscountUsage()
    {
        if (! $this->discountCode) {
            return;
        }

        try {
            $this->authorize('update', $this->cart);

            $this->discountCode = null;
            $this->discountAmount = 0;

            DB::transaction(function () {
                $this->cart->update([
                    'discount_id' => null,
                ]);
            });

            session()->flash('success', 'Penggunaan diskon berhasil dibatalkan.');
            return $this->redirectIntended(route('cart'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('cart'), navigate: true);
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
                    'operation' => 'Canceling discount usage data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan pada saat membatalkan penggunaan diskon, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('cart'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Canceling discount usage data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('cart'), navigate: true);
        }
    }
}; ?>

<div>
    <div class="flex flex-col gap-4 lg:flex-row lg:gap-6">
        @can('view', $this->cart)
            @if (! $items)
                <div class="flex h-full w-full flex-col items-center justify-center">
                    <div class="mb-6 size-72">
                        {!! file_get_contents(public_path('images/illustrations/empty.svg')) !!}
                    </div>
                    <div class="flex flex-col items-center">
                        <h2 class="mb-3 text-center !text-2xl text-black">Keranjang Belanja Anda Masih Kosong</h2>
                        <p class="mb-8 text-center text-base font-normal tracking-tight text-black/70">
                            Seluruh produk yang Anda tambahkan ke dalam keranjang belanja akan ditampilkan disini.
                        </p>
                        <x-common.button :href="route('products')" variant="primary" wire:navigate>
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
                    </div>
                </div>
            @else
                <section class="w-full flex-1 lg:w-2/3" aria-labelledby="cart-product-list-title">
                    <div class="mb-4 flex items-baseline justify-between gap-x-4 lg:justify-start">
                        <h2 id="cart-product-list-title" class="!text-2xl text-black">Daftar Produk</h2>
                        <p class="text-lg font-medium leading-none tracking-tight text-black">
                            ({{ $items->count() }} produk)
                        </p>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        @foreach ($items as $item)
                            <article
                                wire:key="{{ $item->id }}"
                                class="flex items-start gap-x-4 border-b border-b-neutral-300 py-4"
                            >
                                <a
                                    href="{{ $item->category_slug && $item->subcategory_slug ? route('products.detail', ['category' => $item->category_slug, 'subcategory' => $item->subcategory_slug, 'slug' => $item->slug]) : route('products.detail.without.category.subcategory', ['slug' => $item->slug]) }}"
                                    class="size-32 shrink-0 overflow-hidden rounded-lg bg-neutral-100 md:size-36"
                                    wire:navigate
                                >
                                    @if ($item->thumbnail)
                                        <img
                                            src="{{ asset('storage/uploads/product-images/' . $item->thumbnail) }}"
                                            alt="Gambar produk {{ strtolower($item->name) }}"
                                            class="aspect-square h-full w-full scale-100 object-cover brightness-100 transition-all ease-in-out hover:scale-105 hover:brightness-95"
                                            loading="lazy"
                                        />
                                    @else
                                        <div
                                            class="flex aspect-square h-full w-full scale-100 items-center justify-center object-cover brightness-100 transition-all ease-in-out hover:scale-105 hover:brightness-95"
                                        >
                                            <x-common.application-logo
                                                class="block h-8 w-auto fill-current text-primary saturate-0 md:h-12"
                                            />
                                        </div>
                                    @endif
                                </a>
                                <div class="flex min-h-32 w-full flex-col items-start md:min-h-36">
                                    <a
                                        href="{{ $item->category_slug && $item->subcategory_slug ? route('products.detail', ['category' => $item->category_slug, 'subcategory' => $item->subcategory_slug, 'slug' => $item->slug]) : route('products.detail.without.category.subcategory', ['slug' => $item->slug]) }}"
                                        class="mb-0.5"
                                        wire:navigate
                                    >
                                        <h3 class="!text-lg text-black hover:text-primary">
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
                                    <div
                                        class="mt-4 flex flex-col items-start gap-4 md:mt-auto md:flex-row md:items-center md:gap-8"
                                    >
                                        @can('update', $this->cart)
                                            <div class="inline-flex items-center gap-x-2">
                                                <button
                                                    wire:click="decrement('{{ $item->id }}')"
                                                    type="button"
                                                    class="flex size-8 items-center justify-center rounded-md border border-neutral-300 p-2 text-black disabled:cursor-not-allowed disabled:opacity-50"
                                                    aria-label="Kurangi jumlah produk"
                                                    wire:loading.attr="disabled"
                                                    @disabled($item->quantity <= 1)
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
                                                        wire:loading.remove
                                                        wire:target="decrement('{{ $item->id }}')"
                                                    >
                                                        <path d="M5 12h14" />
                                                    </svg>
                                                    <div
                                                        wire:loading
                                                        wire:target="decrement('{{ $item->id }}')"
                                                        class="inline-block size-4 shrink-0 animate-spin rounded-full border-[2px] border-current border-t-transparent text-black"
                                                        role="status"
                                                        aria-label="loading"
                                                    >
                                                        <span class="sr-only">Sedang diproses...</span>
                                                    </div>
                                                </button>
                                                <x-form.input
                                                    id="quantity-{{ $loop->index }}"
                                                    name="quantity"
                                                    type="number"
                                                    class="h-8 w-16 text-center text-black [appearance:textfield] disabled:cursor-not-allowed disabled:opacity-50 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                                    value="{{ $item->quantity }}"
                                                    inputmode="numeric"
                                                    min="1"
                                                    max="{{ $item->stock }}"
                                                    x-on:change="$dispatch('update-item-quantity', { cartItemId: '{{ $item->id }}', newQuantity: $event.target.value })"
                                                    wire:loading.attr="disabled"
                                                    :hasError="$errors->has('quantity-' . $item->id)"
                                                />
                                                <button
                                                    wire:click="increment('{{ $item->id }}')"
                                                    type="button"
                                                    class="flex size-8 items-center justify-center rounded-md border border-neutral-300 p-2 text-black disabled:cursor-not-allowed disabled:opacity-50"
                                                    aria-label="Tambah jumlah produk"
                                                    wire:loading.attr="disabled"
                                                    @disabled($item->quantity >= $item->stock)
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
                                                        wire:loading.remove
                                                        wire:target="increment('{{ $item->id }}')"
                                                    >
                                                        <path d="M5 12h14" />
                                                        <path d="M12 5v14" />
                                                    </svg>
                                                    <div
                                                        class="inline-block size-4 shrink-0 animate-spin rounded-full border-[2px] border-current border-t-transparent text-black"
                                                        role="status"
                                                        aria-label="Sedang diproses..."
                                                        wire:loading
                                                        wire:target="increment('{{ $item->id }}')"
                                                    >
                                                        <span class="sr-only">Sedang diproses...</span>
                                                    </div>
                                                </button>
                                            </div>
                                        @endcan

                                        @can('delete', $this->cart)
                                            <button
                                                wire:click="delete('{{ $item->id }}')"
                                                type="button"
                                                class="inline-flex items-center text-sm font-medium text-red-600 hover:underline"
                                            >
                                                <svg
                                                    class="me-2 size-5 shrink-0"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="currentColor"
                                                    viewBox="0 0 256 256"
                                                    wire:loading.remove
                                                    wire:target="delete('{{ $item->id }}')"
                                                >
                                                    <path
                                                        d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192ZM112,104v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Z"
                                                    />
                                                </svg>
                                                <div
                                                    wire:loading
                                                    wire:target="delete('{{ $item->id }}')"
                                                    class="me-2 inline-block size-4 shrink-0 animate-spin rounded-full border-[2px] border-current border-t-transparent align-middle"
                                                    role="status"
                                                    aria-label="loading"
                                                >
                                                    <span class="sr-only">Sedang diproses...</span>
                                                </div>
                                                <span wire:loading.remove wire:target="delete('{{ $item->id }}')">
                                                    Hapus
                                                </span>
                                                <span wire:loading wire:target="delete('{{ $item->id }}')">
                                                    Sedang diproses...
                                                </span>
                                            </button>
                                        @endcan
                                    </div>
                                    <x-form.input-error
                                        :messages="$errors->get('quantity-' . $item->id)"
                                        class="mt-2"
                                    />
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
                <aside
                    class="relative h-full w-full rounded-md border border-neutral-300 py-4 shadow-md lg:sticky lg:top-20 lg:w-1/3"
                    aria-labelledby="cart-summary-title"
                >
                    <h2 id="cart-summary-title" class="px-4 !text-2xl text-black">Ringkasan Belanja</h2>
                    <hr class="my-4 border-neutral-300" />
                    @can('apply discounts')
                        <div class="px-4">
                            <x-common.button
                                variant="secondary"
                                @class([
                                    'w-full !px-4',
                                    '!bg-primary-50 !text-primary' => $discountCode !== null,
                                ])
                                x-on:click.prevent.stop="$dispatch('open-modal', 'discount-selection')"
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
                                        d="M2 9a3 3 0 1 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 1 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"
                                    />
                                    <path d="M9 9h.01" />
                                    <path d="m15 9-6 6" />
                                    <path d="M15 15h.01" />
                                </svg>
                                <span class="truncate">
                                    {{ $discountCode ? 'Diskon diterapkan: ' . strtoupper($discountCode) : 'Gunakan Diskon' }}
                                </span>
                                <svg
                                    class="ml-auto size-5 shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </x-common.button>
                        </div>
                        <template x-teleport="body">
                            <x-common.modal name="discount-selection" :show="$errors->isNotEmpty()" focusable>
                                <div class="p-4">
                                    <h2 class="mb-2 !text-2xl leading-none text-black">Diskon</h2>
                                    @if ($this->discounts->count() > 0)
                                        <p class="tracking-tight text-black">
                                            Silakan pilih salah satu dari diskon yang tersedia di bawah ini.
                                        </p>
                                        <hr class="my-4 border-neutral-300" />
                                        <ul class="flex h-full max-h-[28rem] w-full flex-col gap-y-2 overflow-y-auto">
                                            @foreach ($this->discounts as $discount)
                                                <li wire:key="{{ $discount->id }}">
                                                    <input
                                                        wire:model.lazy="discountCode"
                                                        id="discount-{{ $discount->code }}"
                                                        name="discount-code"
                                                        type="radio"
                                                        value="{{ $discount->code }}"
                                                        class="peer hidden"
                                                        @checked($discountCode === $discount->code)
                                                        @disabled(! $discount->is_eligible || $discountCode === $discount->code)
                                                    />
                                                    <label
                                                        for="discount-{{ $discount->code }}"
                                                        @class([
                                                            'inline-flex w-full cursor-pointer flex-col items-start rounded-lg border p-4 transition-colors peer-checked:border-primary peer-checked:bg-primary-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50 peer-disabled:hover:bg-white',
                                                            'border-neutral-300 bg-white hover:bg-neutral-100' => $discountCode !== $discount->code,
                                                            'border-primary bg-primary-50 hover:!bg-primary-50' => $discountCode === $discount->code,
                                                        ])
                                                        class=""
                                                        wire:target="discountCode, cancelDiscountUsage"
                                                        wire:loading.class="opacity-50 !cursor-not-allowed"
                                                    >
                                                        <p
                                                            class="mb-2 inline-flex w-full items-center gap-x-2 text-lg font-semibold tracking-tight text-black"
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
                                                                    d="M2 9a3 3 0 1 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 1 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"
                                                                />
                                                                <path d="M9 9h.01" />
                                                                <path d="m15 9-6 6" />
                                                                <path d="M15 15h.01" />
                                                            </svg>
                                                            {{ ucwords($discount->name) }}
                                                            @if ($discountCode === $discount->code)
                                                                <svg
                                                                    class="ml-auto size-5 shrink-0 fill-primary stroke-primary-50"
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
                                                                    <circle cx="12" cy="12" r="10" />
                                                                    <path d="m9 12 2 2 4-4" />
                                                                </svg>
                                                            @endif
                                                        </p>
                                                        <p class="text-base tracking-tight text-black">
                                                            Diskon sebesar
                                                            <span class="mx-1 font-medium text-teal-500">
                                                                {{ $discount->type === 'percentage' ? number_format($discount->value, 0) . '%' : 'Rp' . formatPrice($discount->value) }}
                                                            </span>
                                                            untuk pembelian minimal
                                                            <span class="ms-1 font-medium">
                                                                Rp
                                                                {{ formatPrice($discount->minimum_purchase) }}
                                                            </span>
                                                        </p>
                                                        @if ($discount->type === 'percentage' && $discount->max_discount_amount)
                                                            <div class="inline-flex gap-2">
                                                                <p
                                                                    class="mt-1 text-sm font-medium tracking-tight text-black"
                                                                >
                                                                    (Maksimal potongan diskon: Rp
                                                                    {{ formatPrice($discount->max_discount_amount) }})
                                                                </p>
                                                                <x-common.tooltip
                                                                    id="maximum-discount-off-information"
                                                                    class="z-[3] w-[28rem]"
                                                                    text="Maksimal potongan diskon berlaku sesuai dengan ketentuan. Diskon akan dibatasi agar tidak melebihi jumlah yang ditentukan."
                                                                />
                                                            </div>
                                                        @endif

                                                        <p class="ml-auto mt-4 text-sm tracking-tight text-black/70">
                                                            @if ($discount->start_date && $discount->end_date)
                                                                Diskon berlaku dari
                                                                <time datetime="{{ $discount->start_date }}">
                                                                    {{ formatDate($discount->start_date) }}
                                                                </time>
                                                                -
                                                                <time datetime="{{ $discount->end_date }}">
                                                                    {{ formatDate($discount->end_date) }}
                                                                </time>
                                                            @else
                                                                    Diskon berlaku tanpa batas waktu.
                                                            @endif
                                                        </p>
                                                    </label>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <x-form.input-error :messages="$errors->get('discountCode')" class="mt-2" />
                                        <x-common.button
                                            variant="secondary"
                                            class="mt-4 w-full"
                                            wire:click="cancelDiscountUsage"
                                            wire:loading.class="opacity-50 !cursor-not-allowed"
                                            wire:target="discountCode, cancelDiscountUsage"
                                            :disabled="!$discountCode"
                                        >
                                            <span wire:loading.remove wire:target="discountCode, cancelDiscountUsage">
                                                Batalkan Penggunaan Diskon
                                            </span>
                                            <span
                                                wire:loading.flex
                                                wire:target="discountCode, cancelDiscountUsage"
                                                class="items-center gap-x-2"
                                            >
                                                <div
                                                    class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                                                    role="status"
                                                    aria-label="loading"
                                                >
                                                    <span class="sr-only">Sedang diproses...</span>
                                                </div>
                                                Sedang diproses...
                                            </span>
                                        </x-common.button>
                                    @else
                                        <figure class="flex h-full flex-col items-center justify-center">
                                            <div class="mb-6 size-72">
                                                {!! file_get_contents(public_path('images/illustrations/empty.svg')) !!}
                                            </div>
                                            <figcaption class="flex flex-col items-center">
                                                <h2 class="mb-3 text-center !text-2xl text-black">
                                                    Diskon Tidak Ditemukan
                                                </h2>
                                                <p
                                                    class="mb-8 text-center text-base font-normal tracking-tight text-black/70"
                                                >
                                                    Saat ini, Anda tidak memiliki diskon yang dapat digunakan.
                                                </p>
                                            </figcaption>
                                        </figure>
                                    @endif
                                </div>
                            </x-common.modal>
                        </template>
                    @endcan

                    <hr class="my-4 border-neutral-300" />
                    <dl class="grid grid-cols-2 px-4">
                        <dt class="text-start tracking-tight text-black/70">Total Berat Pengiriman</dt>
                        <dd class="text-end font-medium tracking-tight text-black">
                            {{ formatPrice($totalWeight) }} gram
                        </dd>
                    </dl>
                    <hr class="my-4 border-neutral-300" />
                    <dl class="grid grid-cols-2 gap-y-2 px-4">
                        <dt class="mb-1 text-start tracking-tight text-black/70">Subtotal</dt>
                        <dd class="mb-1 text-end font-medium tracking-tight text-black">
                            Rp {{ formatPrice($totalPrice) }}
                        </dd>
                        <dt class="mb-1 text-start tracking-tight text-black/70">Potongan Diskon</dt>
                        <dd
                            @class([
                                'mb-1 text-end font-medium tracking-tight',
                                'text-black' => ! $discountAmount,
                                'text-teal-500' => $discountAmount,
                            ])
                        >
                            - Rp {{ $discountAmount ? formatPrice($discountAmount) : '0' }}
                        </dd>
                        <dt class="inline-flex gap-x-2 text-start tracking-tight text-black/70">
                            Biaya Pengiriman
                            <x-common.tooltip
                                id="shipping-cost"
                                class="w-52"
                                text="Biaya pengiriman akan dihitung pada halaman checkout berdasarkan kurir dan layanan yang Anda pilih."
                            />
                        </dt>
                        <dd class="text-end font-medium tracking-tight text-black">&mdash;</dd>
                    </dl>
                    <hr class="my-4 border-neutral-300" />
                    <dl class="grid grid-cols-2 px-4">
                        <dt class="text-start tracking-tight text-black/70">Estimasi Total</dt>
                        <dd class="text-end font-semibold tracking-tight text-black">
                            Rp
                            {{ $discountAmount ? formatPrice($totalPrice - $discountAmount) : formatPrice($totalPrice) }}
                        </dd>
                    </dl>
                    <hr class="my-4 border-neutral-300" />
                    <div class="px-4">
                        <x-common.button :href="route('checkout')" class="w-full" variant="primary" wire:navigate>
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
                </aside>
            @endif
        @endcan
    </div>
</div>
