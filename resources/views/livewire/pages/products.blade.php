<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public string $sortBy = 'name_asc';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    public array $categoriesFilter = [];
    public array $subcategoriesFilter = [];
    public array $ratingsFilter = [];

    public function mount()
    {
        $categoryFilter = session()->get('category_filter');
        $subcategoryFilter = session()->get('subcategory_filter');

        if ($categoryFilter || $subcategoryFilter) {
            if ($categoryFilter) {
                array_push($this->categoriesFilter, $categoryFilter);
            }

            if ($subcategoryFilter) {
                array_push($this->subcategoriesFilter, $subcategoryFilter);
            }

            session()->forget('category_filter');
            session()->forget('subcategory_filter');
        }
    }

    public function updatedSortBy()
    {
        switch ($this->sortBy) {
            case 'name_asc':
                $this->sortField = 'name';
                $this->sortDirection = 'asc';
                break;

            case 'name_desc':
                $this->sortField = 'name';
                $this->sortDirection = 'desc';
                break;

            case 'price_asc':
                $this->sortField = 'base_price';
                $this->sortDirection = 'asc';
                break;

            case 'price_desc':
                $this->sortField = 'base_price';
                $this->sortDirection = 'desc';
                break;

            case 'rating_desc':
                $this->sortField = 'average_rating';
                $this->sortDirection = 'desc';
                break;

            case 'newest':
                $this->sortField = 'created_at';
                $this->sortDirection = 'desc';
                break;

            case 'bestseller':
                $this->sortField = 'total_sold';
                $this->sortDirection = 'desc';
                break;

            default:
                $this->sortField = 'name';
                $this->sortDirection = 'asc';
                break;
        }
    }

    #[Computed]
    public function categories()
    {
        return DB::table('categories')
            ->select('id', 'name', 'slug')
            ->get();
    }

    #[Computed]
    public function subcategories()
    {
        return DB::table('subcategories')
            ->select('id', 'name', 'slug')
            ->get();
    }

    #[Computed]
    public function products()
    {
        $validated = $this->validate(
            rules: [
                'search' => 'nullable|string|min:1|max:255',
            ],
        );

        $this->search = strip_tags($validated['search']);

        return Product::with(['images', 'subcategory', 'subcategory.category', 'reviews', 'orderDetails'])
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
            ->withAvg(['reviews as average_rating'], 'rating')
            ->when(! empty($this->categoriesFilter), function ($query) {
                $query->whereHas('subcategory.category', function ($subquery) {
                    $subquery->whereIn('slug', $this->categoriesFilter);
                });
            })
            ->when(! empty($this->subcategoriesFilter), function ($query) {
                $query->whereHas('subcategory', function ($subquery) {
                    $subquery->whereIn('slug', $this->subcategoriesFilter);
                });
            })
            ->when(! empty($this->ratingsFilter), function ($query) {
                $query->whereHas('reviews', function ($subquery) {
                    foreach ($this->ratingsFilter as $rating) {
                        $minRating = $rating;
                        $maxRating = $rating < 5 ? $rating + 0.99 : $rating;
                        $subquery->orHavingRaw('AVG(rating) BETWEEN ? AND ?', [$minRating, $maxRating]);
                    }
                });
            })
            ->when($this->search !== '', function ($query) {
                $query->where(function ($searchQuery) {
                    $searchQuery
                        ->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('subcategory', function ($subquery) {
                            $subquery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('subcategory.category', function ($subquery) {
                            $subquery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->active()
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(9);
    }

    public function resetFilter()
    {
        $this->reset(
            'sortBy',
            'sortField',
            'sortDirection',
            'categoriesFilter',
            'subcategoriesFilter',
            'ratingsFilter',
            'search',
        );
    }
}; ?>

@section('title', 'Produk')

<section class="p-4 md:p-6">
    <div class="mb-8 flex flex-row items-center justify-between">
        <h1>Produk</h1>
        <x-common.button
            variant="secondary"
            class="lg:hidden"
            x-on:click.prevent.stop="$dispatch('open-offcanvas', 'product-responsive-filter')"
        >
            <svg
                class="size-4 shrink-0"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <line x1="21" x2="14" y1="4" y2="4" />
                <line x1="10" x2="3" y1="4" y2="4" />
                <line x1="21" x2="12" y1="12" y2="12" />
                <line x1="8" x2="3" y1="12" y2="12" />
                <line x1="21" x2="16" y1="20" y2="20" />
                <line x1="12" x2="3" y1="20" y2="20" />
                <line x1="14" x2="14" y1="2" y2="6" />
                <line x1="8" x2="8" y1="10" y2="14" />
                <line x1="16" x2="16" y1="18" y2="22" />
            </svg>
            Filter
        </x-common.button>
    </div>
    <div class="flex flex-row lg:divide-x lg:divide-neutral-300">
        <aside
            class="hidden h-full max-h-[calc(100vh-6rem)] w-1/4 overflow-y-auto rounded-lg bg-white lg:sticky lg:top-16 lg:block lg:pe-6"
        >
            <div class="mb-4 flex h-11 items-center justify-start gap-x-4">
                <svg
                    class="size-4 shrink-0"
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <line x1="21" x2="14" y1="4" y2="4" />
                    <line x1="10" x2="3" y1="4" y2="4" />
                    <line x1="21" x2="12" y1="12" y2="12" />
                    <line x1="8" x2="3" y1="12" y2="12" />
                    <line x1="21" x2="16" y1="20" y2="20" />
                    <line x1="12" x2="3" y1="20" y2="20" />
                    <line x1="14" x2="14" y1="2" y2="6" />
                    <line x1="8" x2="8" y1="10" y2="14" />
                    <line x1="16" x2="16" y1="18" y2="22" />
                </svg>
                <h2 class="text-xl font-semibold">Filter Produk</h2>
                @if ($sortBy !== 'name_asc' || ! empty($categoriesFilter) || ! empty($subcategoriesFilter) || ! empty($ratingsFilter) || $search !== '')
                    <x-common.button variant="secondary" class="ml-auto !px-4 !py-2" wire:click="resetFilter">
                        <svg
                            class="size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                            wire:loading.class="animate-spin"
                            wire:target="resetFilter"
                        >
                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                            <path d="M21 3v5h-5" />
                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                            <path d="M8 16H3v5" />
                        </svg>
                        Reset Filter
                    </x-common.button>
                @endif
            </div>
            <div class="mb-4">
                <x-form.input-label
                    for="sort-by"
                    value="Urutkan berdasarkan"
                    :required="false"
                    class="mb-2 !text-lg"
                />
                <select
                    wire:model.lazy="sortBy"
                    id="sort-by"
                    name="sort-by"
                    class="mx-0.5 block w-full rounded-lg border border-neutral-300 p-2.5 text-sm font-medium text-black focus:border-primary focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50"
                    wire:loading.attr="disabled"
                >
                    <option value="name_asc" @selected($sortBy === 'name_asc')>Nama A ke Z</option>
                    <option value="name_desc" @selected($sortBy === 'name_desc')>Nama Z ke A</option>
                    <option value="price_asc" @selected($sortBy === 'price_asc')>Harga Rendah ke Tinggi</option>
                    <option value="price_desc" @selected($sortBy === 'price_desc')>Harga Tinggi ke Rendah</option>
                    <option value="rating_desc" @selected($sortBy === 'rating_desc')>Rating Tertinggi</option>
                    <option value="newest" @selected($sortBy === 'newest')>Produk Terbaru</option>
                    <option value="bestseller" @selected($sortBy === 'bestseller')>Penjualan Terbanyak</option>
                </select>
            </div>
            <div class="mb-4">
                <h4 class="mb-2 text-lg font-medium text-black">Kategori</h4>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($this->categories as $category)
                        <li wire:key="{{ $category->id }}">
                            <input
                                wire:model.lazy="categoriesFilter"
                                type="checkbox"
                                id="{{ $category->name }}"
                                value="{{ $category->slug }}"
                                class="peer hidden"
                            />
                            <label
                                for="{{ $category->name }}"
                                class="flex cursor-pointer items-center justify-between rounded-full border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-black hover:bg-neutral-100 peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
                                wire:loading.class="!cursor-not-allowed opacity-50 hover:bg-white"
                            >
                                {{ ucwords($category->name) }}
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="mb-4">
                <h4 class="mb-2 text-lg font-medium text-black">Subkategori</h4>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($this->subcategories as $subcategory)
                        <li wire:key="{{ $subcategory->id }}">
                            <input
                                wire:model.lazy="subcategoriesFilter"
                                type="checkbox"
                                id="{{ $subcategory->name }}"
                                value="{{ $subcategory->slug }}"
                                class="peer hidden"
                            />
                            <label
                                for="{{ $subcategory->name }}"
                                class="flex cursor-pointer items-center justify-between rounded-full border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-black hover:bg-neutral-100 peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
                                wire:loading.class="!cursor-not-allowed opacity-50 hover:bg-white"
                            >
                                {{ ucwords($subcategory->name) }}
                            </label>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4 class="mb-2 text-lg font-medium text-black">Rating</h4>
                <ul class="flex flex-wrap gap-2">
                    @for ($rate = 5; $rate >= 0; $rate--)
                        <li>
                            <input
                                wire:model.lazy="ratingsFilter"
                                type="checkbox"
                                id="rating-{{ $rate }}"
                                value="{{ $rate }}"
                                class="peer hidden"
                            />
                            <label
                                for="rating-{{ $rate }}"
                                class="flex cursor-pointer items-center justify-between gap-x-2 rounded-full border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-black hover:bg-neutral-100 peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
                                wire:loading.class="!cursor-not-allowed opacity-50 hover:bg-white"
                            >
                                {{ $rate }}
                                <svg
                                    class="size-4 text-yellow-500"
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
                                    <path
                                        d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                                    />
                                </svg>
                            </label>
                        </li>
                    @endfor
                </ul>
            </div>
        </aside>
        <x-common.offcanvas name="product-responsive-filter" position="bottom">
            <x-slot name="title">Filter Produk</x-slot>
            <aside class="bg-white">
                <div class="relative h-full max-h-[29rem] overflow-y-auto">
                    <div class="mb-4">
                        <x-form.input-label
                            for="mobile-sort-by"
                            value="Urutkan berdasarkan"
                            :required="false"
                            class="mb-2 !text-lg"
                        />
                        <select
                            wire:model.lazy="sortBy"
                            id="mobile-sort-by"
                            name="mobile-sort-by"
                            class="block w-full rounded-lg border border-neutral-300 p-2.5 text-sm font-medium text-black focus:border-primary focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50"
                            wire:loading.attr="disabled"
                        >
                            <option value="name_asc" @selected($sortBy === 'name_asc')>Nama A ke Z</option>
                            <option value="name_desc" @selected($sortBy === 'name_desc')>Nama Z ke A</option>
                            <option value="price_asc" @selected($sortBy === 'price_asc')>
                                Harga Rendah ke Tinggi
                            </option>
                            <option value="price_desc" @selected($sortBy === 'price_desc')>
                                Harga Tinggi ke Rendah
                            </option>
                            <option value="rating_desc" @selected($sortBy === 'rating_desc')>Rating Tertinggi</option>
                            <option value="newest" @selected($sortBy === 'newest')>Produk Terbaru</option>
                            <option value="bestseller" @selected($sortBy === 'bestseller')>Penjualan Terbanyak</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <h4 class="mb-2 text-lg font-medium text-black">Kategori</h4>
                        <ul class="flex flex-wrap gap-2">
                            @foreach ($this->categories as $category)
                                <li wire:key="{{ $category->id }}">
                                    <input
                                        wire:model.lazy="categoriesFilter"
                                        type="checkbox"
                                        id="{{ $category->name }}"
                                        value="{{ $category->slug }}"
                                        class="peer hidden"
                                    />
                                    <label
                                        for="{{ $category->name }}"
                                        class="flex cursor-pointer items-center justify-between rounded-full border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-black hover:bg-neutral-100 peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
                                        wire:loading.class="!cursor-not-allowed opacity-50 hover:bg-white"
                                    >
                                        {{ ucwords($category->name) }}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="mb-4">
                        <h4 class="mb-2 text-lg font-medium text-black">Subkategori</h4>
                        <ul class="flex flex-wrap gap-2">
                            @foreach ($this->subcategories as $subcategory)
                                <li wire:key="{{ $subcategory->id }}">
                                    <input
                                        wire:model.lazy="subcategoriesFilter"
                                        type="checkbox"
                                        id="{{ $subcategory->name }}"
                                        value="{{ $subcategory->slug }}"
                                        class="peer hidden"
                                    />
                                    <label
                                        for="{{ $subcategory->name }}"
                                        class="flex cursor-pointer items-center justify-between rounded-full border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-black hover:bg-neutral-100 peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
                                        wire:loading.class="!cursor-not-allowed opacity-50 hover:bg-white"
                                    >
                                        {{ ucwords($subcategory->name) }}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h4 class="mb-2 text-lg font-medium text-black">Rating</h4>
                        <ul class="flex flex-wrap gap-2">
                            @for ($rate = 5; $rate >= 0; $rate--)
                                <li>
                                    <input
                                        wire:model.lazy="ratingsFilter"
                                        type="checkbox"
                                        id="rating-{{ $rate }}"
                                        value="{{ $rate }}"
                                        class="peer hidden"
                                    />
                                    <label
                                        for="rating-{{ $rate }}"
                                        class="flex cursor-pointer items-center justify-between gap-x-2 rounded-full border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-black hover:bg-neutral-100 peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
                                        wire:loading.class="!cursor-not-allowed opacity-50 hover:bg-white"
                                    >
                                        {{ $rate }}
                                        <svg
                                            class="size-4 text-yellow-500"
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
                                            <path
                                                d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                                            />
                                        </svg>
                                    </label>
                                </li>
                            @endfor
                        </ul>
                    </div>
                    <div class="absolute left-1/2 top-52 -translate-x-1/2">
                        <div
                            class="inline-block size-12 animate-spin rounded-full border-[4px] border-current border-t-transparent text-primary"
                            role="status"
                            aria-label="loading"
                            wire:loading
                        >
                            <span class="sr-only">Sedang diproses...</span>
                        </div>
                    </div>
                </div>
                <div class="absolute bottom-0 w-[calc(100%-2rem)] border-t border-t-neutral-300 bg-white py-4">
                    <x-common.button variant="secondary" class="w-full" wire:click="resetFilter">
                        <svg
                            class="size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                            wire:loading.class="animate-spin"
                            wire:target="resetFilter"
                        >
                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                            <path d="M21 3v5h-5" />
                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                            <path d="M8 16H3v5" />
                        </svg>
                        Reset Filter
                    </x-common.button>
                </div>
            </aside>
        </x-common.offcanvas>
        <section class="w-full lg:w-3/4 lg:ps-6">
            <div class="relative grid grid-cols-2 gap-4 md:grid-cols-3 md:gap-6">
                @forelse ($this->products as $product)
                    <x-common.product-card :product="$product" wire:loading.class="opacity-25 cursor-not-allowed" />
                @empty
                    <p class="col-span-2 text-base font-medium tracking-tight text-black md:col-span-3">
                        @if ($search !== '')
                            Produk dengan nama
                            <strong>"{{ $search }}"</strong>
                            tidak ditemukan
                        @else
                            Produk tidak ditemukan
                        @endif
                    </p>
                @endforelse

                <div class="absolute flex h-full w-full items-center justify-center">
                    <div
                        class="inline-block size-12 animate-spin rounded-full border-[4px] border-current border-t-transparent text-primary"
                        role="status"
                        aria-label="loading"
                        wire:loading
                    >
                        <span class="sr-only">Sedang diproses...</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>
