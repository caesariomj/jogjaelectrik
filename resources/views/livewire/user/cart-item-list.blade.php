<?php

use App\Models\Cart;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public string $context = 'offcanvas';

    public ?Cart $cart = null;

    public ?Collection $items = null;

    public float $totalPrice = 0;

    public float $totalWeight = 0;

    public ?string $discountCode = null;

    public ?float $discountAmount = null;

    public function mount($context = 'offcanvas')
    {
        $this->context = $context;

        $this->cart = auth()->check()
            ? auth()
                ->user()
                ->cart()
                ->first()
            : null;

        if ($this->cart) {
            $this->items = $this->cart->items()->exists() ? $this->cart->items : null;

            $this->calculateTotal();
        }
    }

    private function calculateTotal()
    {
        $this->totalPrice = $this->cart ? $this->cart->calculateTotalPrice() : 0;

        $this->totalWeight = $this->cart ? $this->cart->calculateTotalWeight() : 0;

        if ($this->cart && $this->cart->discount_id) {
            $this->discountCode = $this->cart->discount->code;

            $discountModel = \App\Models\Discount::findByCode($this->discountCode)->first();

            $this->discountAmount = $discountModel ? $discountModel->calculateDiscount($this->totalPrice) : null;
        } else {
            $this->discountAmount = 0;
        }
    }

    public function increment(string $cartItemId)
    {
        if ($this->context === 'offcanvas') {
            return;
        }

        $existingCartItem = $this->items->find($cartItemId);

        if (! $existingCartItem) {
            return;
        }

        $newQuantity = $existingCartItem->quantity + 1;

        if ($newQuantity > $existingCartItem->productVariant->stock) {
            $this->addError(
                'quantity-' . $cartItemId,
                'Jumlah kuantitas produk melebihi stok yang tersedia. Stok tersedia:' .
                    $existingCartItem->productVariant->stock,
            );

            return;
        }

        try {
            $this->authorize('update', $this->cart);

            DB::transaction(function () use ($existingCartItem, $newQuantity) {
                $existingCartItem->update([
                    'quantity' => $newQuantity,
                ]);
            });

            $this->calculateTotal();

            return;
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            $errorMessage = $authException->getMessage();

            if ($authException->getCode() === 401) {
                session()->flash('error', $errorMessage);

                return $this->redirectRoute('login', navigate: true);
            }

            session()->flash('error', $errorMessage);

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Illuminate\Database\QueryException $queryException) {
            \Illuminate\Support\Facades\Log::error('Database error during transaction', [
                'error' => $queryException->getMessage(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('Unexpected error occurred', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        }
    }

    #[On('update-item-quantity')]
    public function updateItemQuantity(string $cartItemId, int $quantity)
    {
        if ($this->context === 'offcanvas') {
            return;
        }

        $existingCartItem = $this->items->find($cartItemId);

        if (! $existingCartItem) {
            return;
        }

        if ($quantity < 1) {
            $this->addError('quantity-' . $cartItemId, 'Jumlah produk tidak bisa kurang dari 1.');

            return;
        }

        if ($quantity > $existingCartItem->productVariant->stock) {
            $this->addError(
                'quantity-' . $cartItemId,
                'Jumlah kuantitas produk melebihi stok yang tersedia. Stok tersedia:' .
                    $existingCartItem->productVariant->stock,
            );

            return;
        }

        $quantity = max(1, min($quantity, $existingCartItem->productVariant->stock));

        try {
            $this->authorize('update', $this->cart);

            DB::transaction(function () use ($existingCartItem, $quantity) {
                $existingCartItem->update([
                    'quantity' => $quantity,
                ]);
            });

            $this->calculateTotal();

            return;
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            $errorMessage = $authException->getMessage();

            if ($authException->getCode() === 401) {
                session()->flash('error', $errorMessage);

                return $this->redirectRoute('login', navigate: true);
            }

            session()->flash('error', $errorMessage);

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Illuminate\Database\QueryException $queryException) {
            \Illuminate\Support\Facades\Log::error('Database error during transaction', [
                'error' => $queryException->getMessage(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('Unexpected error occurred', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        }
    }

    public function decrement(string $cartItemId)
    {
        if ($this->context === 'offcanvas') {
            return;
        }

        $existingCartItem = $this->items->find($cartItemId);

        $newQuantity = $existingCartItem->quantity - 1;

        if ($newQuantity < 1) {
            $this->addError('quantity-' . $cartItemId, 'Jumlah produk tidak bisa kurang dari 1.');

            return;
        }

        try {
            $this->authorize('update', $this->cart);

            DB::transaction(function () use ($existingCartItem, $newQuantity) {
                $existingCartItem->update([
                    'quantity' => $newQuantity,
                ]);
            });

            $this->calculateTotal();

            return;
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            $errorMessage = $authException->getMessage();

            if ($authException->getCode() === 401) {
                session()->flash('error', $errorMessage);

                return $this->redirectRoute('login', navigate: true);
            }

            session()->flash('error', $errorMessage);

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Illuminate\Database\QueryException $queryException) {
            \Illuminate\Support\Facades\Log::error('Database error during transaction', [
                'error' => $queryException->getMessage(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('Unexpected error occurred', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        }
    }

    public function delete(string $cartItemId)
    {
        if (! $this->cart) {
            session()->flash('error', 'Keranjang belanja anda kosong.');

            return $this->redirect(request()->header('Referer'), true);
        }

        $existingCartItem = $this->items->find($cartItemId);

        if (! $existingCartItem) {
            session()->flash('error', 'Produk tidak ditemukan pada keranjang belanja anda.');

            return $this->redirect(request()->header('Referer'), true);
        }

        try {
            $this->authorize('delete', $this->cart);

            DB::transaction(function () use ($existingCartItem) {
                $existingCartItem->delete();
            });

            session()->flash('success', 'Produk berhasil dihapus dari keranjang belanja.');

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            $errorMessage = $authException->getMessage();

            if ($authException->getCode() === 401) {
                session()->flash('error', $errorMessage);

                return $this->redirectRoute('login', navigate: true);
            }

            session()->flash('error', $errorMessage);

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Illuminate\Database\QueryException $queryException) {
            \Illuminate\Support\Facades\Log::error('Database error during transaction', [
                'error' => $queryException->getMessage(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('Unexpected error occurred', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        }
    }

    // Todo: Handle selected discount. Ask chatgpt how to handle it securely. Also don't forget the input component validation props :)

    #[Computed]
    public function discounts()
    {
        return \App\Models\Discount::active()
            ->usable()
            ->get();
    }

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

        $discount = \App\Models\Discount::findByCode($this->discountCode)
            ->active()
            ->usable()
            ->first();

        if (! $discount) {
            $this->addError('discountCode', 'Kode diskon tidak valid atau telah kedaluwarsa.');

            return;
        }

        $isDiscountValid = $discount->isValid($this->totalPrice);

        if ($isDiscountValid !== true) {
            $this->addError('discountCode', $isDiscountValid);

            return;
        }

        try {
            $this->authorize('applyDiscount', $this->cart);

            $this->discountCode = $discount->code;

            $this->discountAmount = $discount->calculateDiscount($this->totalPrice);

            DB::transaction(function () use ($discount) {
                $this->cart->update([
                    'discount_id' => $discount->id,
                ]);
            });

            return;
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            $errorMessage = $authException->getMessage();

            if ($authException->getCode() === 401) {
                session()->flash('error', $errorMessage);

                return $this->redirectRoute('login', navigate: true);
            }

            session()->flash('error', $errorMessage);

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Illuminate\Database\QueryException $queryException) {
            \Illuminate\Support\Facades\Log::error('Database error during transaction', [
                'error' => $queryException->getMessage(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('Unexpected error occurred', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
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

            $this->discountAmount = null;

            DB::transaction(function () {
                $this->cart->update([
                    'discount_id' => null,
                ]);
            });

            return;
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            $errorMessage = $authException->getMessage();

            if ($authException->getCode() === 401) {
                session()->flash('error', $errorMessage);

                return $this->redirectRoute('login', navigate: true);
            }

            session()->flash('error', $errorMessage);

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Illuminate\Database\QueryException $queryException) {
            \Illuminate\Support\Facades\Log::error('Database error during transaction', [
                'error' => $queryException->getMessage(),
                'trace' => $queryException->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('Unexpected error occurred', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return $this->redirect(request()->header('Referer'), true);
        }
    }
}; ?>

<div>
    @if ($this->context === 'offcanvas')
        <div class="flex h-[calc(100vh-5.5rem)] flex-col overflow-hidden">
            @if (! $this->cart)
                <figure class="flex h-full flex-col items-center justify-center">
                    <img
                        src="https://placehold.co/400"
                        class="mb-6 size-72 object-cover"
                        alt="Gambar ilustrasi keranjang kosong"
                    />
                    <figcaption class="flex flex-col items-center">
                        <h2 class="mb-3 text-center !text-2xl text-black">Keranjang Belanja Anda Masih Kosong</h2>
                        <p class="mb-8 text-center text-base font-normal tracking-tight text-neutral-600">
                            Seluruh produk yang anda tambahkan ke dalam keranjang belanja akan ditampilkan disini.
                        </p>
                        <x-common.button :href="route('products')" variant="primary" wire:navigate>
                            Belanja Sekarang
                            <svg
                                class="size-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 0 256 256"
                            >
                                <path
                                    d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z"
                                />
                            </svg>
                        </x-common.button>
                    </figcaption>
                </figure>
            @else
                @can('view', $this->cart)
                    @if (! $this->items)
                        <figure class="flex h-full flex-col items-center justify-center">
                            <img
                                src="https://placehold.co/400"
                                class="mb-6 size-72 object-cover"
                                alt="Gambar ilustrasi keranjang kosong"
                            />
                            <figcaption class="flex flex-col items-center">
                                <h2 class="mb-3 text-center !text-2xl text-black">
                                    Keranjang Belanja Anda Masih Kosong
                                </h2>
                                <p class="mb-8 text-center text-base font-normal tracking-tight text-neutral-600">
                                    Seluruh produk yang anda tambahkan ke dalam keranjang belanja akan ditampilkan
                                    disini.
                                </p>
                                <x-common.button :href="route('products')" variant="primary" wire:navigate>
                                    Belanja Sekarang
                                    <svg
                                        class="size-5"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="currentColor"
                                        viewBox="0 0 256 256"
                                    >
                                        <path
                                            d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z"
                                        />
                                    </svg>
                                </x-common.button>
                            </figcaption>
                        </figure>
                    @else
                        <div class="flex-1 overflow-y-auto">
                            @foreach ($this->items as $item)
                                <article
                                    wire:key="{{ $item->id }}"
                                    class="flex items-start gap-x-4 border-b border-b-neutral-300 py-4"
                                >
                                    <a
                                        href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
                                        class="h-28 w-28 overflow-hidden rounded-lg bg-neutral-100"
                                        wire:navigate
                                    >
                                        <img
                                            src="{{ asset('uploads/product-images/' .$item->productVariant->product->images()->thumbnail()->first()->file_name,) }}"
                                            alt="Gambar produk {{ strtolower($item->productVariant->product->name) }}"
                                            class="aspect-square h-full w-full scale-100 object-cover brightness-100 transition-all ease-in-out hover:scale-105 hover:brightness-95"
                                            loading="lazy"
                                        />
                                    </a>
                                    <div class="flex flex-col items-start">
                                        <a
                                            href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
                                            class="mb-0.5"
                                            wire:navigate
                                        >
                                            <h3 class="!text-lg text-neutral-900 hover:text-primary">
                                                {{ $item->productVariant->product->name }}
                                            </h3>
                                        </a>

                                        @if ($item->productVariant->variant_sku)
                                            <p class="mb-2 text-sm tracking-tight text-black">
                                                {{ ucwords($item->productVariant->combinations->first()->variationVariant->variation->name) . ': ' . ucwords($item->productVariant->combinations->first()->variationVariant->name) }}
                                            </p>
                                        @endif

                                        <p
                                            class="inline-flex items-center text-sm font-medium tracking-tighter text-neutral-600 sm:text-base"
                                        >
                                            <span class="me-2">{{ $item->quantity }}</span>
                                            x
                                            <span class="ms-2 tracking-tight text-neutral-900">
                                                Rp {{ formatPrice($item->price) }}
                                            </span>
                                        </p>
                                        @can('delete', $this->cart)
                                            <button
                                                wire:click="delete('{{ $item->id }}')"
                                                type="button"
                                                class="mt-4 inline-flex items-center text-sm font-medium text-red-600 hover:underline"
                                            >
                                                <svg
                                                    wire:loading.remove
                                                    wire:target="delete('{{ $item->id }}')"
                                                    class="me-2 size-5"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="currentColor"
                                                    viewBox="0 0 256 256"
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
                                </article>
                            @endforeach
                        </div>
                        <div class="mt-auto border-t border-t-neutral-300 p-4">
                            <div class="mb-4 inline-flex w-full items-center justify-between">
                                <p class="text-base font-semibold tracking-tighter text-neutral-900">Subtotal:</p>
                                <span class="text-lg font-bold tracking-tighter text-neutral-900">
                                    Rp {{ formatPrice($totalPrice) }}
                                </span>
                            </div>
                            <x-common.button :href="route('cart')" class="w-full" variant="primary" wire:navigate>
                                Keranjang Belanja
                            </x-common.button>
                        </div>
                    @endif
                @endcan
            @endif
        </div>
    @else
        <div class="flex flex-col gap-4 lg:flex-row lg:gap-6">
            @if (! $this->cart)
                <figure class="flex h-full flex-col items-center justify-center">
                    <img
                        src="https://placehold.co/400"
                        class="mb-6 size-72 object-cover"
                        alt="Gambar ilustrasi keranjang kosong"
                    />
                    <figcaption class="flex flex-col items-center">
                        <h3 class="mb-3 text-center !text-2xl text-black">Keranjang Belanja Anda Masih Kosong</h3>
                        <p class="mb-8 text-center text-base font-normal tracking-tight text-neutral-600">
                            Seluruh produk yang anda tambahkan ke dalam keranjang belanja akan ditampilkan disini.
                        </p>
                        <x-common.button :href="route('products')" variant="primary" wire:navigate>
                            Belanja Sekarang
                            <svg
                                class="size-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 0 256 256"
                            >
                                <path
                                    d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z"
                                />
                            </svg>
                        </x-common.button>
                    </figcaption>
                </figure>
            @else
                @can('view', $this->cart)
                    @if (! $this->items)
                        <figure class="flex h-full w-full flex-col items-center justify-center">
                            <img
                                src="https://placehold.co/400"
                                class="mb-6 size-72 object-cover"
                                alt="Gambar ilustrasi keranjang kosong"
                            />
                            <figcaption class="flex flex-col items-center">
                                <h3 class="mb-3 text-center !text-2xl text-black">
                                    Keranjang Belanja Anda Masih Kosong
                                </h3>
                                <p class="mb-8 text-center text-base font-normal tracking-tight text-neutral-600">
                                    Seluruh produk yang anda tambahkan ke dalam keranjang belanja akan ditampilkan
                                    disini.
                                </p>
                                <x-common.button :href="route('products')" variant="primary" wire:navigate>
                                    Belanja Sekarang
                                    <svg
                                        class="size-5"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="currentColor"
                                        viewBox="0 0 256 256"
                                    >
                                        <path
                                            d="M221.66,133.66l-72,72a8,8,0,0,1-11.32-11.32L196.69,136H40a8,8,0,0,1,0-16H196.69L138.34,61.66a8,8,0,0,1,11.32-11.32l72,72A8,8,0,0,1,221.66,133.66Z"
                                        />
                                    </svg>
                                </x-common.button>
                            </figcaption>
                        </figure>
                    @else
                        <section class="w-full flex-1 lg:w-2/3" aria-labelledby="cart-product-list-title">
                            <div class="mb-4 flex items-baseline justify-between gap-x-4 lg:justify-start">
                                <h2 id="product-list-title" class="!text-2xl text-black">Daftar Produk</h2>
                                <p class="text-lg font-medium leading-none tracking-tight text-black">
                                    ({{ $this->items->count() }} produk)
                                </p>
                            </div>
                            <div class="flex-1 overflow-y-auto">
                                @foreach ($this->items as $item)
                                    <article
                                        wire:key="{{ $item->id }}"
                                        class="flex items-start gap-x-4 border-b border-b-neutral-300 py-4"
                                    >
                                        <a
                                            href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
                                            class="size-40 shrink-0 overflow-hidden rounded-lg bg-neutral-100"
                                            wire:navigate
                                        >
                                            <img
                                                src="{{ asset('uploads/product-images/' .$item->productVariant->product->images()->thumbnail()->first()->file_name,) }}"
                                                alt="Gambar produk {{ strtolower($item->productVariant->product->name) }}"
                                                class="aspect-square h-full w-40 scale-100 object-cover brightness-100 transition-all ease-in-out hover:scale-105 hover:brightness-95"
                                                loading="lazy"
                                            />
                                        </a>
                                        <div class="flex h-40 w-full flex-col items-start">
                                            <a
                                                href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
                                                class="mb-0.5"
                                                wire:navigate
                                            >
                                                <h3 class="!text-lg text-neutral-900 hover:text-primary">
                                                    {{ $item->productVariant->product->name }}
                                                </h3>
                                            </a>

                                            @if ($item->productVariant->variant_sku)
                                                <p class="mb-2 text-sm tracking-tight text-black">
                                                    {{ ucwords($item->productVariant->combinations->first()->variationVariant->variation->name) . ': ' . ucwords($item->productVariant->combinations->first()->variationVariant->name) }}
                                                </p>
                                            @endif

                                            <p
                                                class="inline-flex items-center text-sm font-medium tracking-tighter text-neutral-600 sm:text-base"
                                            >
                                                <span class="me-2">{{ $item->quantity }}</span>
                                                x
                                                <span class="ms-2 tracking-tight text-neutral-900">
                                                    Rp {{ formatPrice($item->price) }}
                                                </span>
                                            </p>
                                            <div class="mt-auto flex items-center gap-x-8">
                                                @can('update', $this->cart)
                                                    <div class="inline-flex items-center gap-x-2">
                                                        <button
                                                            wire:click="decrement('{{ $item->id }}')"
                                                            type="button"
                                                            class="flex size-8 items-center justify-center rounded-md border border-neutral-300 p-2 text-black disabled:cursor-not-allowed disabled:opacity-50"
                                                            aria-label="Kurangi kuantitas produk"
                                                            wire:loading.attr="disabled"
                                                            @disabled($item->quantity <= 1)
                                                        >
                                                            <svg
                                                                wire:loading.remove
                                                                wire:target="decrement('{{ $item->id }}')"
                                                                class="size-4 shrink-0"
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                viewBox="0 0 24 24"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="2"
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
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
                                                            max="{{ $item->productVariant->stock }}"
                                                            x-on:change="$dispatch('update-item-quantity', { cartItemId: '{{ $item->id }}', quantity: $event.target.value })"
                                                            wire:loading.attr="disabled"
                                                            :hasError="$errors->has('quantity-' . $item->id)"
                                                        />
                                                        <button
                                                            wire:click="increment('{{ $item->id }}')"
                                                            type="button"
                                                            class="flex size-8 items-center justify-center rounded-md border border-neutral-300 p-2 text-black disabled:cursor-not-allowed disabled:opacity-50"
                                                            aria-label="Tambah kuantitas produk"
                                                            wire:loading.attr="disabled"
                                                            @disabled($item->quantity >= $item->productVariant->stock)
                                                        >
                                                            <svg
                                                                wire:loading.remove
                                                                wire:target="increment('{{ $item->id }}')"
                                                                class="size-4 shrink-0"
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                viewBox="0 0 24 24"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="2"
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                            >
                                                                <path d="M5 12h14" />
                                                                <path d="M12 5v14" />
                                                            </svg>
                                                            <div
                                                                wire:loading
                                                                wire:target="increment('{{ $item->id }}')"
                                                                class="inline-block size-4 shrink-0 animate-spin rounded-full border-[2px] border-current border-t-transparent text-black"
                                                                role="status"
                                                                aria-label="loading"
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
                                                            wire:loading.remove
                                                            wire:target="delete('{{ $item->id }}')"
                                                            class="me-2 size-5"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            fill="currentColor"
                                                            viewBox="0 0 256 256"
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
                            class="relative h-full w-full lg:sticky lg:top-20 lg:w-1/3"
                            aria-labelledby="cart-summary-title"
                        >
                            <h2 id="cart-summary-title" class="!text-2xl text-black">Ringkasan Belanja</h2>
                            <hr class="my-4 border-neutral-300" />
                            @if ($this->discounts->count() > 0)
                                @can('apply discounts')
                                    <x-common.button
                                        variant="secondary"
                                        class="w-full !px-4"
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
                                            {{ $discountCode ? 'Kode diskon: ' . strtoupper($discountCode) : 'Gunakan Diskon' }}
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
                                @endcan
                            @endif

                            <hr class="my-4 border-neutral-300" />
                            <dl class="grid grid-cols-2">
                                <dt class="text-start tracking-tight text-black/70">Total Berat Pengiriman</dt>
                                <dd class="text-end font-medium tracking-tight text-black">
                                    {{ formatPrice($totalWeight) }} gram
                                </dd>
                            </dl>
                            <hr class="my-4 border-neutral-300" />
                            <dl class="grid grid-cols-2 gap-y-2">
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
                                    @if ($discountAmount)
                                        - Rp {{ formatPrice($discountAmount) }}
                                    @else
                                        &mdash;
                                    @endif
                                </dd>

                                <dt class="inline-flex gap-x-2 text-start tracking-tight text-black/70">
                                    Biaya Pengiriman
                                    <x-common.tooltip
                                        id="shipping-cost"
                                        text="Biaya pengiriman akan dihitung pada halaman checkout."
                                    />
                                </dt>
                                <dd class="text-end font-medium tracking-tight text-black">&mdash;</dd>
                                <dt class="inline-flex gap-x-2 text-start tracking-tight text-black/70">
                                    Biaya Layanan
                                    <x-common.tooltip
                                        id="service-cost"
                                        text="Biaya layanan akan dihitung pada halaman checkout."
                                    />
                                </dt>
                                <dd class="text-end font-medium tracking-tight text-black">&mdash;</dd>
                            </dl>
                            <hr class="my-4 border-neutral-300" />
                            <dl class="grid grid-cols-2">
                                <dt class="text-start tracking-tight text-black/70">Estimasi Total</dt>
                                <dd class="text-end font-medium tracking-tight text-black">
                                    Rp
                                    {{ $discountAmount ? formatPrice($totalPrice - $discountAmount) : formatPrice($totalPrice) }}
                                </dd>
                            </dl>
                            <hr class="mb-8 mt-4 border-neutral-300" />
                            <x-common.button :href="route('checkout')" class="w-full" variant="primary" wire:navigate>
                                Checkout
                            </x-common.button>
                        </aside>
                    @endif
                @endcan
            @endif
        </div>
    @endif
    @if ($this->discounts->count() > 0)
        @can('apply discounts')
            <x-common.modal name="discount-selection" :show="$errors->isNotEmpty()" focusable>
                <div class="p-4">
                    <h2 class="mb-2 !text-2xl leading-none text-black">Diskon</h2>
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
                                    @disabled($totalPrice < $discount->minimum_purchase)
                                />
                                <label
                                    for="discount-{{ $discount->code }}"
                                    @class([
                                        'inline-flex w-full cursor-pointer flex-col items-start rounded-lg border p-4 peer-checked:border-primary peer-checked:bg-primary-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50 peer-disabled:hover:bg-white',
                                        'border-neutral-300 bg-white hover:bg-neutral-100' => $discountCode !== $discount->code,
                                        'border-primary bg-primary-50' => $discountCode === $discount->code,
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
                                            >
                                                <circle cx="12" cy="12" r="10" />
                                                <path d="m9 12 2 2 4-4" />
                                            </svg>
                                        @endif
                                    </p>
                                    <p class="mb-4 text-base tracking-tight text-black">
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
                                    <p class="ml-auto text-sm tracking-tight text-black/70">
                                        @if ($discount->start_date && $discount->end_date)
                                            Diskon berlaku dari
                                            <time datetime="{{ $discount->start_date }}">
                                                {{ formatDate($discount->start_date) }}
                                            </time>
                                            hingga
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
                        x-on:click="$wire.cancelDiscountUsage()"
                        wire:target="discountCode, cancelDiscountUsage"
                        wire:loading.class="opacity-50 !cursor-not-allowed"
                        :disabled="!$discountCode"
                    >
                        Batalkan Penggunaan Diskon
                    </x-common.button>
                </div>
            </x-common.modal>
        @endcan
    @endif
</div>
