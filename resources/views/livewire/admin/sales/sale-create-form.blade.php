<?php

use App\Livewire\Forms\SaleForm;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithoutUrlPagination;

    public SaleForm $form;

    public string $search = '';

    public function mount(): void
    {
        $this->form->setSale();
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::queryAllWithRelations(
            columns: ['products.id', 'products.name', 'products.main_sku', 'products.is_active'],
            relations: ['thumbnail', 'variations'],
        )
            ->when($this->search !== '', function ($query) {
                return $query->where('products.name', 'like', '%' . $this->search . '%');
            })
            ->paginate(12);
    }

    public function addItems(array $productVariant): void
    {
        $variants = $productVariant['variants'] ?? [];
        $hasMultipleVariants = count($variants) > 1 || ($variants[0]['name'] ?? null) !== null;

        $existingIndex = collect($this->form->items)->search(fn ($item) => $item['id'] === $productVariant['id']);

        if ($existingIndex !== false) {
            if ($hasMultipleVariants) {
                $existingProduct = &$this->form->items[$existingIndex];
                $existingVariantIds = array_column($existingProduct['variants'], 'id');

                $newVariants = collect($variants)
                    ->reject(fn ($variant) => in_array($variant['id'], $existingVariantIds))
                    ->map(fn ($variant) => array_merge($variant, ['quantity' => 1]))
                    ->values()
                    ->all();

                $existingProduct['variants'] = array_merge($existingProduct['variants'], $newVariants);
            }
        } else {
            $variants = array_map(
                fn ($variant) => array_merge($variant, ['quantity' => 1]),
                $productVariant['variants'],
            );

            $this->form->items[] = [
                'id' => $productVariant['id'],
                'name' => $productVariant['name'],
                'variants' => $variants,
            ];
        }

        $this->updateTotalPrice();
    }

    public function updateQuantity(string $variantId, int $quantity): void
    {
        foreach ($this->form->items as &$item) {
            foreach ($item['variants'] as &$variant) {
                if ($variant['id'] === $variantId && $quantity > 0 && $quantity <= $variant['stock']) {
                    $variant['quantity'] = $quantity;
                }
            }
        }

        unset($item, $variant);

        $this->updateTotalPrice();
    }

    public function deleteItems(string $variantId): void
    {
        foreach ($this->form->items as $itemIndex => $item) {
            foreach ($item['variants'] as $variantIndex => $variant) {
                if ($variant['id'] === $variantId) {
                    unset($this->form->items[$itemIndex]['variants'][$variantIndex]);

                    $this->form->items[$itemIndex]['variants'] = array_values(
                        $this->form->items[$itemIndex]['variants'],
                    );

                    if (empty($this->form->items[$itemIndex]['variants'])) {
                        unset($this->form->items[$itemIndex]);
                    }

                    break 2;
                }
            }
        }

        $this->form->items = array_values($this->form->items);

        $this->updateTotalPrice();
    }

    public function updateTotalPrice(): void
    {
        $totalPrice = 0;

        foreach ($this->form->items as $item) {
            foreach ($item['variants'] as $variant) {
                $totalPrice += $variant['price'] * $variant['quantity'];
            }
        }

        $this->form->totalPrice = formatPrice($totalPrice);
    }

    public function resetSearch(): void
    {
        $this->reset('search');
    }

    /**
     * Create new sale.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to create new sale.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function save()
    {
        $validated = $this->form->validate();

        try {
            $this->authorize('create', Order::class);

            DB::transaction(function () use ($validated) {
                $order = Order::create([
                    'source' => $validated['source'],
                    'status' => 'completed',
                    'subtotal_amount' => $validated['totalPrice'],
                    'total_amount' => $validated['totalPrice'],
                ]);

                foreach ($validated['items'] as $item) {
                    foreach ($item['variants'] as $variant) {
                        $productVariant = ProductVariant::where('id', $variant['id'])->first();

                        $orderDetail = $order->details()->create([
                            'product_variant_id' => $productVariant->id,
                            'price' => $productVariant->price_discount ?? $productVariant->price,
                            'quantity' => $variant['quantity'],
                        ]);

                        $productVariant->update([
                            'stock' => (int) $productVariant->stock - $orderDetail->quantity,
                        ]);
                    }
                }
            });

            session()->flash('success', 'Penjualan berhasil ditambahkan.');
            $this->redirectRoute('admin.sales.index', navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.sales.index'), navigate: true);
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
                    'operation' => 'Creating offline sales',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menambahkan penjualan baru, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.sales.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Creating offline sales',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.sales.index'), navigate: true);
        }
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow">
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-lg text-black">Item Produk</h2>
        </legend>
        <div class="p-4">
            <div class="relative w-full shrink">
                <div class="pointer-events-none absolute inset-y-0 start-0 z-20 flex items-center ps-4">
                    <svg
                        class="size-4 shrink-0 text-black/70"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </div>
                <div class="relative">
                    <x-form.input
                        id="search-input"
                        name="search-input"
                        wire:model.live.debounce.250ms="search"
                        class="block w-full !border-neutral-100 !bg-neutral-100 !px-12"
                        type="text"
                        placeholder="Cari produk berdasarkan nama..."
                        autocomplete="off"
                    />
                    <div
                        wire:loading
                        wire:target="search,resetSearch"
                        class="pointer-events-none absolute end-0 top-1/2 -translate-y-1/2 pe-4"
                    >
                        <svg
                            class="size-5 shrink-0 animate-spin text-black"
                            fill="currentColor"
                            viewBox="0 0 256 256"
                            aria-hidden="true"
                        >
                            <path
                                d="M232,128a104,104,0,0,1-208,0c0-41,23.81-78.36,60.66-95.27a8,8,0,0,1,6.68,14.54C60.15,61.59,40,93.27,40,128a88,88,0,0,0,176,0c0-34.73-20.15-66.41-51.34-80.73a8,8,0,0,1,6.68-14.54C208.19,49.64,232,87,232,128Z"
                            />
                        </svg>
                    </div>
                    @if ($search)
                        <button
                            type="button"
                            class="absolute end-0 top-1/2 -translate-y-1/2 pe-3"
                            aria-label="Reset pencarian"
                            wire:click="resetSearch"
                            wire:loading.remove
                            wire:target="search,resetSearch"
                        >
                            <svg class="size-5 shrink-0 text-black" fill="currentColor" viewBox="0 0 256 256">
                                <path
                                    d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"
                                />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
            <ul class="mt-8 grid grid-cols-2 gap-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($this->products as $product)
                    <li wire:key="{{ $product->id }}" class="flex flex-col items-center gap-2">
                        <div
                            class="aspect-square w-36 overflow-hidden rounded-lg outline outline-1 outline-neutral-300 md:w-44"
                        >
                            <img
                                src="{{ asset('storage/uploads/product-images/' . $product->thumbnail) }}"
                                class="h-full w-full object-cover"
                                alt="Gambar produk {{ $product->name }}"
                                loading="lazy"
                            />
                        </div>
                        <p class="text-sm font-medium text-black">
                            {{ $product->name }}
                        </p>
                        @if ($product->variant_skus)
                            <x-common.button
                                variant="secondary"
                                x-on:click.prevent="$dispatch('open-modal', 'variant-selection-popup-{{ $product->id }}')"
                                class="mt-2 w-fit"
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
                                    <path d="M5 12h14" />
                                    <path d="M12 5v14" />
                                </svg>
                                Tambah
                            </x-common.button>
                            <template x-teleport="body">
                                <x-common.modal
                                    wire:key="{{ $product->id }}"
                                    name="variant-selection-popup-{{ $product->id }}"
                                    :show="$errors->isNotEmpty()"
                                    focusable
                                >
                                    <div
                                        x-data="{
                                            selectedVariants: [],
                                            variationName: '{{ $product->variation_name }}',
                                            variants: @js(explode('||', $product->variant_names)),
                                            variantIds: @js(explode('||', $product->variant_ids)),
                                            variantPrices: @js(explode('||', $product->variant_prices)),
                                            variantPriceDiscounts: @js(explode('||', $product->variant_price_discounts)),
                                            variantStocks: @js(explode('||', $product->variant_stocks)),
                                            selectVariant(index) {
                                                if (this.variantStocks[index] <= 0) return

                                                if (this.selectedVariants.includes(index)) {
                                                    this.selectedVariants = this.selectedVariants.filter(
                                                        (i) => i !== index,
                                                    )
                                                } else {
                                                    this.selectedVariants.push(index)
                                                }
                                            },
                                            submit() {
                                                if (this.selectedVariants.length === 0) return

                                                const variants = this.selectedVariants.map((index) => ({
                                                    id: this.variantIds[index],
                                                    name: this.variants[index],
                                                    price:
                                                        this.variantPriceDiscounts[index] > 0.0
                                                            ? this.variantPriceDiscounts[index]
                                                            : this.variantPrices[index],
                                                    stock: this.variantStocks[index],
                                                }))

                                                $wire.addItems({
                                                    id: '{{ $product->id }}',
                                                    name: '{{ $product->name }}',
                                                    variants: variants,
                                                })

                                                this.$dispatch('close')
                                            },
                                        }"
                                        class="mx-auto w-full max-w-lg space-y-8 p-6"
                                    >
                                        <div class="text-center tracking-tight">
                                            <h2 class="text-lg font-semibold">{{ $product->name }}</h2>
                                            <p class="mt-1 text-sm text-black/70">Pilih variasi</p>
                                        </div>
                                        <div class="space-y-2">
                                            <p class="text-sm font-medium capitalize" x-text="variationName"></p>
                                            <div class="mt-1 flex flex-wrap gap-2">
                                                <template x-for="(variant, index) in variants" :key="index">
                                                    <button
                                                        type="button"
                                                        class="min-w-24 rounded-full border px-4 py-3 text-sm capitalize transition-all"
                                                        :class="{
                                                            'bg-black text-white border-black': selectedVariants.includes(index),
                                                            'bg-white hover:bg-neutral-200 border-black': !selectedVariants.includes(index) && variantStocks[index] > 0,
                                                            'opacity-50 cursor-not-allowed': variantStocks[index] <= 0
                                                        }"
                                                        x-on:click="selectVariant(index)"
                                                        x-text="variantStocks[index] > 0 ? variant : variant + ' (Habis)'"
                                                    ></button>
                                                </template>
                                            </div>
                                        </div>
                                        <div
                                            class="flex w-full flex-col items-center justify-end gap-4 pt-8 md:flex-row"
                                        >
                                            <x-common.button
                                                x-on:click="$dispatch('close')"
                                                variant="secondary"
                                                class="w-full md:w-fit"
                                            >
                                                Batal
                                            </x-common.button>
                                            <button
                                                type="button"
                                                class="w-full rounded-full bg-primary px-8 py-3 font-medium text-white hover:bg-primary-600 disabled:pointer-events-none disabled:opacity-50 md:w-fit"
                                                x-on:click="submit"
                                                :disabled="selectedVariants.length === 0"
                                            >
                                                Tambah Item
                                            </button>
                                        </div>
                                    </div>
                                </x-common.modal>
                            </template>
                        @else
                            <div
                                x-data="{
                                    submit() {
                                        const payload = {
                                            id: '{{ $product->id }}',
                                            name: '{{ $product->name }}',
                                            variants: [
                                                {
                                                    id: '{{ $product->variant_ids }}',
                                                    name: null,
                                                    price: '{{ $product->variant_price_discounts > 0.0 ? $product->variant_price_discounts : $product->variant_prices }}',
                                                    stock: '{{ $product->variant_stocks }}',
                                                },
                                            ],
                                        }

                                        $wire.addItems(payload)
                                    },
                                }"
                            >
                                <x-common.button variant="secondary" x-on:click="submit" class="mt-2 w-fit">
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
                                        <path d="M5 12h14" />
                                        <path d="M12 5v14" />
                                    </svg>
                                    Tambah
                                </x-common.button>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div class="mt-16">
                {{ $this->products->links('components.common.pagination') }}
            </div>
        </div>
    </fieldset>
    @if (count($this->form->items) > 0)
        <div class="flex flex-col">
            <div class="overflow-x-auto md:-mx-1.5">
                <div class="inline-block min-w-full p-4 align-middle">
                    <div class="overflow-hidden rounded-lg border border-neutral-300">
                        <table class="w-full table-fixed divide-y divide-neutral-300 bg-white">
                            <thead>
                                <tr>
                                    <th
                                        scope="col"
                                        class="w-4 border-r border-neutral-300 p-3 text-center text-sm font-medium tracking-tight text-black/70"
                                    >
                                        No.
                                    </th>
                                    <th
                                        scope="col"
                                        class="w-40 border-r border-neutral-300 px-6 py-3 text-center text-sm font-medium tracking-tight text-black/70"
                                    >
                                        Nama Produk
                                    </th>
                                    <th
                                        scope="col"
                                        class="w-40 border-r border-neutral-300 px-6 py-3 text-center text-sm font-medium tracking-tight text-black/70"
                                    >
                                        Varian
                                    </th>
                                    <th
                                        scope="col"
                                        class="w-40 border-r border-neutral-300 px-6 py-3 text-center text-sm font-medium tracking-tight text-black/70"
                                    >
                                        Kuantitas
                                    </th>
                                    <th
                                        scope="col"
                                        class="w-20 border-l border-neutral-300 px-6 py-3 text-center text-sm font-medium tracking-tight text-black/70"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-300">
                                @foreach ($this->form->items as $index => $item)
                                    @foreach ($item['variants'] as $variant)
                                        <tr>
                                            @if ($loop->first)
                                                <td
                                                    class="w-4 whitespace-nowrap border-r border-neutral-300 p-3 text-center text-sm tracking-tight text-black"
                                                    rowspan="{{ count($item['variants']) }}"
                                                >
                                                    {{ $loop->iteration . '.' }}
                                                </td>
                                                <td
                                                    class="w-40 whitespace-nowrap border-r border-neutral-300 px-6 py-3 text-center text-sm tracking-tight text-black"
                                                    rowspan="{{ count($item['variants']) }}"
                                                >
                                                    {{ $item['name'] }}
                                                </td>
                                            @endif

                                            <td
                                                class="w-40 whitespace-nowrap border-r border-neutral-300 px-6 py-3 text-center text-sm tracking-tight text-black"
                                            >
                                                {{ $variant['name'] ?? '-' }}
                                            </td>
                                            <td
                                                x-data="{
                                                    variantId: '{{ $variant['id'] }}',
                                                    quantity: {{ $variant['quantity'] }},
                                                    stock: {{ $variant['stock'] }},
                                                    increaseQty() {
                                                        if (this.quantity >= this.stock) return

                                                        this.quantity++

                                                        $wire.updateQuantity(this.variantId, this.quantity)
                                                    },
                                                    decreaseQty() {
                                                        if (this.quantity === 1) return

                                                        this.quantity--

                                                        $wire.updateQuantity(this.variantId, this.quantity)
                                                    },
                                                }"
                                                class="flex w-full items-center justify-center gap-x-4 whitespace-nowrap border-neutral-300 px-6 py-3"
                                                align="center"
                                            >
                                                <button
                                                    type="button"
                                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 text-black transition-colors hover:bg-neutral-200 disabled:pointer-events-none disabled:opacity-50"
                                                    x-on:click="decreaseQty"
                                                >
                                                    <svg
                                                        class="size-4 shrink-0"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 256 256"
                                                    >
                                                        <path
                                                            d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128Z"
                                                        ></path>
                                                    </svg>
                                                </button>
                                                <p>{{ $variant['quantity'] }}</p>
                                                <button
                                                    type="button"
                                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-100 text-black transition-colors hover:bg-neutral-200 disabled:pointer-events-none disabled:opacity-50"
                                                    x-on:click="increaseQty"
                                                >
                                                    <svg
                                                        class="size-4 shrink-0"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 256 256"
                                                    >
                                                        <path
                                                            d="M224,128a8,8,0,0,1-8,8H136v80a8,8,0,0,1-16,0V136H40a8,8,0,0,1,0-16h80V40a8,8,0,0,1,16,0v80h80A8,8,0,0,1,224,128Z"
                                                        ></path>
                                                    </svg>
                                                </button>
                                            </td>
                                            <td
                                                class="w-12 items-center whitespace-nowrap border-l border-neutral-300 px-6 py-3"
                                                align="center"
                                            >
                                                <button
                                                    type="button"
                                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-500 transition-colors hover:bg-red-500 hover:text-white disabled:pointer-events-none disabled:opacity-50"
                                                    x-on:click="$wire.deleteItems('{{ $variant['id'] }}')"
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
                                                    >
                                                        <path
                                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"
                                                        />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <fieldset>
        <legend class="flex w-full border-y border-neutral-300 p-4">
            <h2 class="text-lg text-black">Detail Penjualan</h2>
        </legend>
        <div class="space-y-4 p-4">
            <div class="flex flex-col gap-4 lg:flex-row">
                <div class="w-full lg:w-1/2">
                    <x-form.input-label class="mb-1" for="transaction-time" value="Waktu Transaksi" />
                    <x-form.input
                        wire:model.lazy="form.transactionTime"
                        id="transaction-time"
                        class="block w-full"
                        type="text"
                        name="transaction-time"
                        readonly
                        disabled
                        :hasError="$errors->has('form.transactionTime')"
                    />
                    <x-form.input-error :messages="$errors->get('form.transactionTime')" class="mt-2" />
                </div>
                <div class="w-full lg:w-1/2">
                    <x-form.input-label class="mb-1" for="sale-method" value="Metode Penjualan" />
                    <x-form.input
                        wire:model.lazy="form.source"
                        id="sale-method"
                        class="block w-full capitalize"
                        type="text"
                        name="sale-method"
                        readonly
                        disabled
                        :hasError="$errors->has('form.source')"
                    />
                    <x-form.input-error :messages="$errors->get('form.source')" class="mt-2" />
                </div>
            </div>
            <div class="w-full">
                <x-form.input-label class="mb-1" for="total-price" value="Total Penjualan" />
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                        <span class="text-sm tracking-tight text-black/70">Rp</span>
                    </div>
                    <x-form.input
                        wire:model.lazy="form.totalPrice"
                        id="total-price"
                        class="block w-full ps-11"
                        type="text"
                        inputmode="numeric"
                        name="total-price"
                        readonly
                        disabled
                        :hasError="$errors->has('form.totalPrice')"
                    />
                </div>
                <x-form.input-error :messages="$errors->get('form.totalPrice')" class="mt-2" />
            </div>
        </div>
    </fieldset>
    <div class="space-y-1 p-4">
        @foreach ($this->form->items as $itemIndex => $item)
            <x-form.input-error :messages="$errors->get('form.items.' . $itemIndex . '.id')" />
            <x-form.input-error :messages="$errors->get('form.items.' . $itemIndex . '.name')" />
            <x-form.input-error :messages="$errors->get('form.items.' . $itemIndex . '.variants')" />
            @foreach ($item['variants'] as $variantIndex => $variant)
                <x-form.input-error
                    :messages="$errors->get('form.items.' . $itemIndex . '.variants.' . $variantIndex . '.id')"
                />
                <x-form.input-error
                    :messages="$errors->get('form.items.' . $itemIndex . '.variants.' . $variantIndex . '.quantity')"
                />
            @endforeach
        @endforeach
    </div>
    <div class="flex flex-col justify-end gap-4 p-4 md:flex-row">
        <x-common.button
            :href="route('admin.sales.index')"
            variant="secondary"
            wire:loading.class="!pointers-event-none !cursor-not-allowed opacity-50"
            wire:target="save"
            wire:navigate
        >
            Batal
        </x-common.button>
        <x-common.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Simpan</span>
            <span wire:loading.flex wire:target="save" class="items-center gap-x-2">
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
    </div>
</form>
