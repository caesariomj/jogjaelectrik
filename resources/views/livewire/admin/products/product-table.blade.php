<?php

use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    #[Locked]
    public bool $archived = false;

    public function mount($archived = false)
    {
        $this->archived = $archived;
    }

    #[Computed]
    public function products()
    {
        $products = Product::query()
            ->with(['images', 'subcategory'])
            ->withSum('variants', 'stock')
            ->withSum(
                [
                    'orderDetails as total_sold' => function ($query) {
                        $query->whereHas('order', function ($q) {
                            $q->where('status', 'completed');
                        });
                    },
                ],
                'quantity',
            )
            ->when($this->search !== '', function ($query) {
                return $query->where('name', 'like', '%' . $this->search . '%');
            });

        if ($this->archived) {
            $products->onlyTrashed();
        }

        if ($this->sortField === 'subcategory_name') {
            $products
                ->join('subcategories', 'products.subcategory_id', '=', 'subcategories.id')
                ->orderBy('subcategories.name', $this->sortDirection)
                ->select('products.*');
        } elseif ($this->sortField === 'total_stock') {
            $products->orderBy('variants_sum_stock', $this->sortDirection);
        } else {
            $products->orderBy($this->sortField, $this->sortDirection);
        }

        return $products->paginate(10);
    }

    public function resetSearch()
    {
        $this->reset('search');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    public function changeStatus(string $id)
    {
        if ($this->archived) {
            return;
        }

        $product = Product::find($id);

        if (! $product) {
            session()->flash('error', 'Produk tidak ditemukan.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        }

        try {
            $this->authorize('update', $product);

            DB::transaction(function () use ($product) {
                $product->update([
                    'is_active' => $product->is_active ? 0 : 1,
                ]);
            });

            $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

            session()->flash('success', 'Produk ' . $product->name . ' berhasil ' . $status . '.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database error during product status alteration: ' . $e->getMessage());

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah status produk ' .
                    $product->name .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected product status alteration error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        }
    }

    public function archive(string $id)
    {
        if ($this->archived) {
            return;
        }

        $product = Product::find($id);

        if (! $product) {
            session()->flash('error', 'Produk tidak ditemukan.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        }

        try {
            $this->authorize('delete', $product);

            DB::transaction(function () use ($product) {
                $product->delete();
            });

            session()->flash('success', 'Produk ' . $product->name . ' berhasil diarsip.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database error during product archivation: ' . $e->getMessage());

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengarsip produk ' . $product->name . ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected product archivation error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        }
    }

    public function restore(string $id)
    {
        if (! $this->archived) {
            return;
        }

        $product = Product::onlyTrashed()->find($id);

        if (! $product) {
            session()->flash('error', 'Produk tidak ditemukan.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        }

        try {
            $this->authorize('restore', $product);

            DB::transaction(function () use ($product) {
                $product->restore();
            });

            session()->flash('success', 'Produk ' . $product->name . ' berhasil dipulihkan.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database error during product restoration: ' . $e->getMessage());

            session()->flash(
                'error',
                'Terjadi kesalahan dalam memulihkan produk ' . $product->name . ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected product restoration error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        }
    }

    public function delete(string $id)
    {
        if (! $this->archived) {
            return;
        }

        $product = Product::onlyTrashed()
            ->withSum(
                [
                    'orderDetails as total_sold' => function ($query) {
                        $query->whereHas('order', function ($q) {
                            $q->where('status', 'completed');
                        });
                    },
                ],
                'quantity',
            )
            ->find($id);

        if (! $product) {
            session()->flash('error', 'Produk tidak ditemukan.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        }

        if ((int) $product->total_sold > 0) {
            session()->flash('error', 'Anda tidak dapat menghapus produk ini karena produk telah terjual.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        }

        $productName = $product->name;

        try {
            $this->authorize('forceDelete', $product);

            DB::transaction(function () use ($product) {
                foreach ($product->images as $image) {
                    $filePath = 'product-images/' . $image->file_name;

                    if (Storage::disk('public_uploads')->exists($filePath)) {
                        Storage::disk('public_uploads')->delete($filePath);
                    }
                }

                $product->forceDelete();
            });

            session()->flash('success', 'Produk ' . $productName . ' berhasil dihapus.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database error during product deletion: ' . $e->getMessage());

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menghapus produk ' . $productName . ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected product deletion error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        }
    }
}; ?>

<div>
    <div class="border-b border-neutral-300 pb-4">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 start-0 z-20 flex items-center ps-3.5">
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
                    id="product-search"
                    name="product-search"
                    wire:model.live.debounce.250ms="search"
                    class="block w-full ps-10"
                    type="text"
                    role="combobox"
                    placeholder="Cari data produk berdasarkan nama..."
                />
                <div
                    wire:loading
                    wire:target="search,resetSearch"
                    class="pointer-events-none absolute end-0 top-1/2 -translate-y-1/2 pe-3"
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
                        wire:click="resetSearch"
                        wire:loading.remove
                        wire:target="search,resetSearch"
                        type="button"
                        class="absolute end-0 top-1/2 -translate-y-1/2 pe-3"
                    >
                        <svg
                            class="size-5 shrink-0 text-black"
                            fill="currentColor"
                            viewBox="0 0 256 256"
                            aria-hidden="true"
                        >
                            <path
                                d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z"
                            />
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>
    <div class="relative w-full overflow-hidden overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-neutral-300">
                <tr>
                    <th scope="col" class="p-4 text-sm font-semibold tracking-tight text-black" align="left">No.</th>
                    <th scope="col" align="left">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('name')"
                        >
                            Nama
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'name' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'name' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" align="left">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('subcategory_name')"
                        >
                            Subkategori Terkait
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'subcategory_name' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'subcategory_name' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('total_sold')"
                        >
                            Total Terjual
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'total_sold' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'total_sold' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('total_stock')"
                        >
                            Total Stok
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'total_stock' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'total_stock' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('base_price')"
                        >
                            Harga
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'base_price' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'base_price' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('is_active')"
                        >
                            Status
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'is_active' && $sortDirection === 'desc',
                                    ])
                                    points="80 176 128 224 176 176"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'is_active' && $sortDirection === 'asc',
                                    ])
                                    points="80 80 128 32 176 80"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="16"
                                />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="p-4" align="right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-300">
                @forelse ($this->products as $product)
                    <tr
                        wire:key="{{ $product->id }}"
                        wire:loading.class="opacity-50"
                        wire:target="search,sortBy,resetSearch"
                    >
                        <td class="p-4 font-normal tracking-tight text-black/70" align="left">
                            {{ $loop->index + 1 . '.' }}
                        </td>
                        <td class="h-full p-4 align-middle font-medium tracking-tight text-black" align="left">
                            <div class="flex h-full items-stretch gap-x-4">
                                @php
                                    $thumbnailImageFileName =
                                        $product
                                            ->images()
                                            ->thumbnail()
                                            ->first()->file_name ?? null;
                                @endphp

                                @if ($thumbnailImageFileName)
                                    <div class="flex h-full flex-shrink-0 items-center">
                                        <div
                                            class="aspect-square h-full w-14 shrink-0 overflow-hidden rounded-md border border-neutral-300"
                                        >
                                            <img
                                                class="h-full w-full object-cover"
                                                src="{{ asset('storage/uploads/product-images/' . $thumbnailImageFileName) }}"
                                                alt="Gambar utama produk {{ $product->name }}"
                                            />
                                        </div>
                                    </div>
                                @else
                                    <div class="flex h-full flex-shrink-0 items-center">
                                        <div
                                            class="flex aspect-square h-full w-14 shrink-0 items-center justify-center rounded-md bg-neutral-200"
                                        >
                                            <x-common.application-logo class="h-6 w-6 text-black opacity-50" />
                                        </div>
                                    </div>
                                @endif

                                <div class="flex min-w-72 flex-1 items-center">
                                    <span class="whitespace-normal break-words">{{ ucwords($product->name) }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-4 font-normal tracking-tight text-black/70" align="left">
                            @if ($product->subcategory_id)
                                {{ ucwords($product->subcategory->name) }}
                            @else
                                    Produk belum terkait pada subkategori
                            @endif
                        </td>
                        <td class="p-4 font-normal tracking-tight text-black/70" align="center">
                            {{ $product->total_sold ? formatPrice($product->total_sold) : 0 }}
                        </td>
                        <td class="p-4 font-normal tracking-tight text-black/70" align="center">
                            {{ formatPrice($product->totalStock()) }}
                        </td>
                        <td class="h-full p-4 align-middle font-normal tracking-tight text-black/70" align="center">
                            <div class="flex h-full min-w-24 flex-col items-stretch gap-y-1">
                                @if ($product->variants->count() > 1)
                                    <span>Mulai dari</span>
                                @endif

                                @if ($product->base_price_discount)
                                    Rp {{ formatPrice($product->base_price_discount) }}
                                    <del class="text-xs text-black/50">Rp {{ formatPrice($product->base_price) }}</del>
                                @else
                                    Rp {{ formatPrice($product->base_price) }}
                                @endif
                            </div>
                        </td>
                        <td class="p-4 font-normal tracking-tight text-black/70" align="center">
                            @if ($product->trashed())
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-medium tracking-tight text-red-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-red-800"></span>
                                    Diarsipkan
                                </span>
                            @elseif ($product->is_active)
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                                    Aktif
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium tracking-tight text-yellow-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-yellow-800"></span>
                                    Non-Aktif
                                </span>
                            @endif
                        </td>
                        <td class="relative px-4 py-2" align="right">
                            <x-common.dropdown width="48">
                                <x-slot name="trigger">
                                    <button
                                        type="button"
                                        class="rounded-full p-2 text-black hover:bg-neutral-200 disabled:hover:bg-white"
                                        wire:loading.attr="disabled"
                                        wire:target="search,sortBy,resetSearch"
                                    >
                                        <svg
                                            class="size-4"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <circle cx="12" cy="12" r="1" />
                                            <circle cx="12" cy="5" r="1" />
                                            <circle cx="12" cy="19" r="1" />
                                        </svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @if ($this->archived)
                                        @can('restore products')
                                            <x-common.dropdown-link
                                                type="button"
                                                wire:click="restore('{{ $product->id }}')"
                                            >
                                                <svg
                                                    class="size-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <rect width="20" height="5" x="2" y="3" rx="1" />
                                                    <path d="M4 8v11a2 2 0 0 0 2 2h2" />
                                                    <path d="M20 8v11a2 2 0 0 1-2 2h-2" />
                                                    <path d="m9 15 3-3 3 3" />
                                                    <path d="M12 12v9" />
                                                </svg>
                                                Pulihkan
                                            </x-common.dropdown-link>
                                        @endcan

                                        @if ($product->total_sold < 0)
                                            @can('force delete products')
                                                <x-common.dropdown-link
                                                    x-on:click.prevent.stop="$dispatch('open-modal', 'confirm-product-deletion-{{ $product->id }}')"
                                                    class="text-red-500 hover:bg-red-50"
                                                >
                                                    <svg
                                                        class="size-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    >
                                                        <path d="M3 6h18" />
                                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                                        <line x1="10" x2="10" y1="11" y2="17" />
                                                        <line x1="14" x2="14" y1="11" y2="17" />
                                                    </svg>
                                                    Hapus
                                                </x-common.dropdown-link>
                                                <template x-teleport="body">
                                                    <x-common.modal
                                                        name="confirm-product-deletion-{{ $product->id }}"
                                                        :show="$errors->isNotEmpty()"
                                                        focusable
                                                    >
                                                        <div class="flex flex-col items-center p-6">
                                                            <div
                                                                class="mb-4 rounded-full bg-red-100 p-4"
                                                                aria-hidden="true"
                                                            >
                                                                <svg
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 24 24"
                                                                    fill="currentColor"
                                                                    class="size-16 text-red-500"
                                                                >
                                                                    <path
                                                                        fill-rule="evenodd"
                                                                        d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                                                                        clip-rule="evenodd"
                                                                    />
                                                                </svg>
                                                            </div>
                                                            <h2 class="mb-2 text-center text-black">
                                                                Hapus Produk {{ ucwords($product->name) }}
                                                            </h2>
                                                            <p
                                                                class="mb-8 text-center text-base font-medium tracking-tight text-black/70"
                                                            >
                                                                Apakah anda yakin ingin menghapus produk
                                                                <strong>"{{ strtolower($product->name) }}"</strong>
                                                                ini ? Proses ini tidak dapat dibatalkan, seluruh data
                                                                yang terkait dengan produk ini akan dihapus dari sistem.
                                                            </p>
                                                            <div class="flex justify-end gap-4">
                                                                <x-common.button
                                                                    variant="secondary"
                                                                    x-on:click="$dispatch('close')"
                                                                    wire:loading.class="!pointers-event-nonte !cursor-not-allowed opacity-50"
                                                                    wire:target="delete('{{ $product->id }}')"
                                                                >
                                                                    Batal
                                                                </x-common.button>
                                                                <x-common.button
                                                                    wire:click="delete('{{ $product->id }}')"
                                                                    variant="danger"
                                                                    wire:loading.attr="disabled"
                                                                    wire:target="delete('{{ $product->id }}')"
                                                                >
                                                                    <span
                                                                        wire:loading.remove
                                                                        wire:target="delete('{{ $product->id }}')"
                                                                    >
                                                                        Hapus Produk
                                                                    </span>
                                                                    <span
                                                                        wire:loading.flex
                                                                        wire:target="delete('{{ $product->id }}')"
                                                                        class="items-center gap-x-2"
                                                                    >
                                                                        <div
                                                                            class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                                                                            role="status"
                                                                            aria-label="loading"
                                                                        >
                                                                            <span class="sr-only">
                                                                                Sedang diproses...
                                                                            </span>
                                                                        </div>
                                                                        Sedang diproses...
                                                                    </span>
                                                                </x-common.button>
                                                            </div>
                                                        </div>
                                                    </x-common.modal>
                                                </template>
                                            @endcan
                                        @endif
                                    @else
                                        <x-common.dropdown-link
                                            :href="route('admin.products.show', ['slug' => $product->slug])"
                                            x-on:click="event.stopPropagation()"
                                            wire:navigate
                                        >
                                            <svg
                                                class="size-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="m3 10 2.5-2.5L3 5" />
                                                <path d="m3 19 2.5-2.5L3 14" />
                                                <path d="M10 6h11" />
                                                <path d="M10 12h11" />
                                                <path d="M10 18h11" />
                                            </svg>
                                            Detail
                                        </x-common.dropdown-link>

                                        @can('edit products')
                                            <x-common.dropdown-link
                                                wire:click="changeStatus('{{ $product->id }}')"
                                                x-on:click="event.stopPropagation()"
                                                wire:loading.class="!cursor-wait opacity-50"
                                                wire:target="changeStatus('{{ $product->id }}')"
                                            >
                                                <svg
                                                    class="size-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <rect width="20" height="12" x="2" y="6" rx="6" ry="6" />
                                                    <circle cx="8" cy="12" r="2" />
                                                </svg>
                                                <span wire:loading.remove>
                                                    {{ $product->is_active ? 'Non Aktifkan Produk' : 'Aktifkan Produk' }}
                                                </span>
                                                <span wire:loading wire:target="changeStatus('{{ $product->id }}')">
                                                    Sedang diproses
                                                </span>
                                            </x-common.dropdown-link>
                                            <x-common.dropdown-link
                                                :href="route('admin.products.edit', ['slug' => $product->slug])"
                                                x-on:click="event.stopPropagation()"
                                                wire:navigate
                                            >
                                                <svg
                                                    class="size-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <path
                                                        d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"
                                                    />
                                                    <path d="m15 5 4 4" />
                                                </svg>
                                                Ubah
                                            </x-common.dropdown-link>
                                        @endcan

                                        @can('archive products')
                                            <x-common.dropdown-link
                                                x-on:click.prevent.stop="$dispatch('open-modal', 'confirm-product-archiving-{{ $product->id }}')"
                                                class="text-red-500 hover:bg-red-50"
                                            >
                                                <svg
                                                    class="size-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >
                                                    <rect width="20" height="5" x="2" y="3" rx="1" />
                                                    <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                                                    <path d="M10 12h4" />
                                                </svg>
                                                Arsip
                                            </x-common.dropdown-link>
                                            <template x-teleport="body">
                                                <x-common.modal
                                                    name="confirm-product-archiving-{{ $product->id }}"
                                                    :show="$errors->isNotEmpty()"
                                                    focusable
                                                >
                                                    <div class="flex flex-col items-center p-6">
                                                        <div
                                                            class="mb-4 rounded-full bg-red-100 p-4"
                                                            aria-hidden="true"
                                                        >
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                viewBox="0 0 24 24"
                                                                fill="currentColor"
                                                                class="size-16 text-red-500"
                                                            >
                                                                <path
                                                                    fill-rule="evenodd"
                                                                    d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                                                                    clip-rule="evenodd"
                                                                />
                                                            </svg>
                                                        </div>
                                                        <h2 class="mb-2 text-center text-black">
                                                            Arsip Produk {{ ucwords($product->name) }}
                                                        </h2>
                                                        <p
                                                            class="mb-8 text-center text-base font-medium tracking-tight text-black/70"
                                                        >
                                                            Apakah anda yakin ingin mengarsip produk
                                                            <strong class="text-black">
                                                                "{{ strtolower($product->name) }}"
                                                            </strong>
                                                            ini ? Produk yang diarsip tidak akan dapat dilihat maupun
                                                            dibeli oleh pelanggan. Anda dapat melihat produk yang di
                                                            arsip pada menu
                                                            <a
                                                                href="{{ route('admin.archived-products.index') }}"
                                                                class="underline"
                                                                wire:navigate
                                                            >
                                                                arsip produk
                                                            </a>
                                                            .
                                                        </p>
                                                        <div class="flex justify-end gap-4">
                                                            <x-common.button
                                                                variant="secondary"
                                                                x-on:click="$dispatch('close')"
                                                                wire:loading.class="!pointers-event-nonte !cursor-not-allowed opacity-50"
                                                                wire:target="archive('{{ $product->id }}')"
                                                            >
                                                                Batal
                                                            </x-common.button>
                                                            <x-common.button
                                                                wire:click="archive('{{ $product->id }}')"
                                                                variant="danger"
                                                                wire:loading.attr="disabled"
                                                                wire:target="archive('{{ $product->id }}')"
                                                            >
                                                                <span
                                                                    wire:loading.remove
                                                                    wire:target="archive('{{ $product->id }}')"
                                                                >
                                                                    Arsip Produk
                                                                </span>
                                                                <span
                                                                    wire:loading.flex
                                                                    wire:target="archive('{{ $product->id }}')"
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
                                                        </div>
                                                    </div>
                                                </x-common.modal>
                                            </template>
                                        @endcan
                                    @endif
                                </x-slot>
                            </x-common.dropdown>
                        </td>
                    </tr>
                @empty
                    <tr wire:loading.class="opacity-50" wire:target="search,sortBy,resetSearch">
                        <td class="p-4" colspan="8">
                            <figure class="my-4 flex h-full flex-col items-center justify-center">
                                <img
                                    src="https://placehold.co/400"
                                    class="mb-6 size-72 object-cover"
                                    alt="Gambar ilustrasi produk tidak ditemukan"
                                />
                                <figcaption class="flex flex-col items-center">
                                    <h2 class="mb-3 text-center !text-2xl text-black">Produk Tidak Ditemukan</h2>
                                    <p class="mb-8 text-center text-base font-normal tracking-tight text-black/70">
                                        @if ($search)
                                            Produk yang Anda cari tidak ditemukan, silakan coba untuk mengubah kata kunci
                                        pencarian Anda.
                                        @else
                                            @if ($archived)
                                                Seluruh produk Anda yang diarsipkan akan ditampilkan di halaman ini.
                                            @else
                                                Seluruh produk Anda akan ditampilkan di halaman ini. Anda dapat
                                                menambahkan produk baru dengan menekan tombol
                                                <strong>Tambah</strong>
                                                diatas.
                                            @endif
                                        @endif
                                    </p>
                                </figcaption>
                            </figure>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div
            class="absolute left-1/2 top-16 h-full -translate-x-1/2"
            wire:loading
            wire:target="search,sortBy,resetSearch"
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
    {{ $this->products->links() }}
</div>
