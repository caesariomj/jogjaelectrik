<?php

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component {
    public string $category = '';

    public string $subcategory = '';

    #[Url(as: 'sort', except: '')]
    public string $sortBy = '';

    public string $sortField = '';

    public string $sortDirection = '';

    public array $subcategoryFilters = [];

    public array $ratingFilters = [];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(string $category = '', string $subcategory = '', string $search = ''): void
    {
        $this->category = $category;
        $this->subcategory = $subcategory;
        $this->search = $search;
    }

    /**
     * Get a paginated list of products with thumbnail, category, subcategory, and rating.
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::queryAllWithRelations(
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
            ->when($this->category, function ($query) {
                return $query->where('categories.slug', 'like', '%' . $this->category . '%');
            })
            ->when($this->subcategory, function ($query) {
                return $query->where('subcategories.slug', 'like', '%' . $this->subcategory . '%');
            })
            ->when($this->search, function ($query) {
                return $query
                    ->where('products.name', 'like', '%' . $this->search . '%')
                    ->orWhere('subcategories.name', 'like', '%' . $this->search . '%')
                    ->orWhere('categories.name', 'like', '%' . $this->search . '%');
            })
            ->when(! empty($this->subcategoryFilters), function ($query) {
                return $query->whereIn('subcategories.slug', $this->subcategoryFilters);
            })
            ->when(! empty($this->ratingFilters), function ($query) {
                foreach ($this->ratingFilters as $rating) {
                    $minRating = $rating;
                    $maxRating = $rating < 5 ? $rating + 0.99 : $rating;

                    $query->orHavingRaw('average_rating BETWEEN ? AND ?', [$minRating, $maxRating]);
                }

                return $query;
            })
            ->when($this->sortField && $this->sortDirection, function ($query) {
                if ($this->sortField === 'products.total_sold') {
                    return $query->orderByDesc(
                        DB::table('order_details')
                            ->join('product_variants', 'product_variants.id', '=', 'order_details.product_variant_id')
                            ->selectRaw('COALESCE(SUM(order_details.quantity), 0)')
                            ->whereColumn('product_variants.product_id', 'products.id'),
                    );
                } else {
                    return $query->orderBy($this->sortField, $this->sortDirection);
                }
            })
            ->paginate(12);
    }

    /**
     * Sort the products by the specified field.
     *
     * @return  void
     */
    public function updatedSortBy(): void
    {
        if (
            ! in_array($this->sortBy, [
                'nama-asc',
                'nama-desc',
                'harga-asc',
                'harga-desc',
                'diskon',
                'rating-desc',
                'terbaru',
                'terlaris',
            ])
        ) {
            $this->sortBy = '';

            return;
        }

        switch ($this->sortBy) {
            case 'nama-asc':
                $this->sortField = 'products.name';
                $this->sortDirection = 'asc';
                break;

            case 'nama-desc':
                $this->sortField = 'products.name';
                $this->sortDirection = 'desc';
                break;

            case 'harga-asc':
                $this->sortField = 'products.base_price';
                $this->sortDirection = 'asc';
                break;

            case 'harga-desc':
                $this->sortField = 'products.base_price';
                $this->sortDirection = 'desc';
                break;

            case 'diskon':
                $this->sortField = 'products.base_price_discount';
                $this->sortDirection = 'desc';
                break;

            case 'rating-desc':
                $this->sortField = 'products.average_rating';
                $this->sortDirection = 'desc';
                break;

            case 'terbaru':
                $this->sortField = 'products.created_at';
                $this->sortDirection = 'desc';
                break;

            case 'terlaris':
                $this->sortField = 'products.total_sold';
                $this->sortDirection = 'desc';
                break;

            default:
                $this->sortBy = '';
                $this->sortField = '';
                $this->sortDirection = '';
                break;
        }
    }

    /**
     * Remove subcategory filter.
     *
     * @param   string  $subcategory - The subcategory value to remove.
     *
     * @return  void
     */
    public function removeSubcategoryFilter(string $subcategory): void
    {
        if (($key = array_search($subcategory, $this->subcategoryFilters)) !== false) {
            unset($this->subcategoryFilters[$key]);
        }
    }

    /**
     * Remove rating filter.
     *
     * @param   string  $rating - The rating value to remove.
     *
     * @return  void
     */
    public function removeRatingFilter(string $rating): void
    {
        if (($key = array_search($rating, $this->ratingFilters)) !== false) {
            unset($this->ratingFilters[$key]);
        }
    }

    /**
     * Reset applied filter.
     *
     * @return  void
     */
    public function resetFilter(): void
    {
        $this->subcategoryFilters = [];

        $this->ratingFilters = [];
    }
}; ?>

<div>
    <div class="flex flex-row items-start gap-12">
        <aside
            class="hidden lg:sticky lg:top-20 lg:block lg:w-72 lg:shrink-0 lg:rounded-md lg:border lg:border-neutral-300 lg:shadow-md"
        >
            <ul class="flex flex-col divide-y divide-neutral-300">
                <li x-data="{ isCategoryFilterExpanded: true }" class="p-2">
                    <button
                        id="category-accordion-controls"
                        type="button"
                        class="focus-visible:outline-hidden flex w-full items-center justify-between gap-4 rounded-md px-4 py-2 text-left font-medium tracking-tight text-black underline-offset-2 transition-colors hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:underline"
                        :class="isCategoryFilterExpanded ? 'bg-neutral-100' : 'bg-white'"
                        aria-controls="category-accordion"
                        x-on:click="isCategoryFilterExpanded = ! isCategoryFilterExpanded"
                        x-bind:aria-expanded="isCategoryFilterExpanded ? 'true' : 'false'"
                    >
                        Kategori
                        <svg
                            class="size-4 shrink-0 transition-transform"
                            :class="isCategoryFilterExpanded ? 'rotate-180' : ''"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke-width="2"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div
                        id="category-accordion"
                        role="region"
                        aria-labelledby="category-accordion-controls"
                        x-show="isCategoryFilterExpanded"
                        x-collapse
                        x-cloak
                    >
                        <ul class="mt-2 flex flex-col gap-y-2">
                            @foreach ($primaryCategories as $category)
                                <li x-data="{ isSubcategoryExpanded: false }" class="ms-4">
                                    <button
                                        id="subcategory-accordion-controls"
                                        type="button"
                                        class="focus-visible:outline-hidden flex w-full items-center justify-between gap-4 rounded-md px-4 py-2 text-left font-medium tracking-tight text-black underline-offset-2 transition-colors hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:underline"
                                        :class="isSubcategoryExpanded ? 'bg-neutral-100' : 'bg-white'"
                                        aria-controls="subcategory-accordion"
                                        x-on:click="isSubcategoryExpanded = ! isSubcategoryExpanded"
                                        x-bind:aria-expanded="isSubcategoryExpanded ? 'true' : 'false'"
                                    >
                                        {{ ucwords($category->name) }}
                                        <svg
                                            class="size-4 shrink-0 transition-transform"
                                            :class="isSubcategoryExpanded ? 'rotate-180' : ''"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke-width="2"
                                            stroke="currentColor"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                            />
                                        </svg>
                                    </button>
                                    <div
                                        id="subcategory-accordion"
                                        role="region"
                                        aria-labelledby="subcategory-accordion-controls"
                                        x-show="isSubcategoryExpanded"
                                        x-collapse
                                        x-cloak
                                    >
                                        <ul class="mt-4 flex flex-col items-start gap-y-4">
                                            @foreach ($category->subcategories as $subcategory)
                                                <li class="ms-4 inline-flex items-center">
                                                    <x-form.checkbox
                                                        wire:model.lazy="subcategoryFilters"
                                                        id="{{ $subcategory->slug }}-subcategory-filter"
                                                        name="{{ $subcategory->slug }}-subcategory-filter"
                                                        value="{{ $subcategory->slug }}"
                                                        :hasError="$errors->has('subcategoryFilters')"
                                                    />
                                                    <label
                                                        for="{{ $subcategory->slug }}-subcategory-filter"
                                                        class="ms-2 text-base font-medium leading-none tracking-tight text-black"
                                                    >
                                                        {{ ucwords($subcategory->name) }}
                                                    </label>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
                <li x-data="{ isRatingFilterExpanded: false }" class="p-2">
                    <button
                        id="rating-accordion-controls"
                        type="button"
                        class="focus-visible:outline-hidden flex w-full items-center justify-between gap-4 rounded-md px-4 py-2 text-left font-medium tracking-tight text-black underline-offset-2 transition-colors hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:underline"
                        :class="isRatingFilterExpanded ? 'bg-neutral-100' : 'bg-white'"
                        aria-controls="rating-accordion"
                        x-on:click="isRatingFilterExpanded = ! isRatingFilterExpanded"
                        x-bind:aria-expanded="isRatingFilterExpanded ? 'true' : 'false'"
                    >
                        Rating
                        <svg
                            class="size-4 shrink-0 transition-transform"
                            :class="isRatingFilterExpanded ? 'rotate-180' : ''"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke-width="2"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div
                        id="rating-accordion"
                        role="region"
                        aria-labelledby="rating-accordion-controls"
                        x-show="isRatingFilterExpanded"
                        x-collapse
                        x-cloak
                    >
                        <ul class="mt-4 flex flex-col items-start gap-y-4">
                            @for ($rating = 5; $rating > 0; $rating--)
                                <li class="ms-4 inline-flex items-center">
                                    <x-form.checkbox
                                        wire:model.lazy="ratingFilters"
                                        id="{{ $rating }}-rating-filter"
                                        name="{{ $rating }}-rating-filter"
                                        value="{{ $rating }}"
                                        :hasError="$errors->has('ratingFilters')"
                                    />
                                    <label
                                        for="{{ $rating }}-rating-filter"
                                        class="ms-2 inline-flex items-center gap-x-1 text-base font-medium leading-none tracking-tight text-black"
                                    >
                                        <svg
                                            class="size-4 shrink-0 text-yellow-500"
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
                                        {{ $rating }}
                                    </label>
                                </li>
                            @endfor
                        </ul>
                    </div>
                </li>
                <li class="p-2">
                    <x-common.button
                        variant="secondary"
                        class="w-full"
                        :disabled="empty($subcategoryFilters) && empty($ratingFilters)"
                        wire:click="resetFilter"
                        wire:loading.attr="disabled"
                        wire:target="resetFilter"
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
                            wire:loading.class="animate-spin"
                            wire:target="resetFilter"
                        >
                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                            <path d="M21 3v5h-5" />
                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                            <path d="M8 16H3v5" />
                        </svg>
                        <span wire:loading.remove wire:target="resetFilter">Reset Filter</span>
                        <span wire:loading wire:target="resetFilter">Sedang Diproses...</span>
                    </x-common.button>
                </li>
            </ul>
        </aside>
        <x-common.offcanvas name="product-catalog-filter" position="bottom">
            <x-slot name="title">
                <h2 id="label-cart-offcanvas-summary" class="text-xl font-semibold text-black">Filter Produk</h2>
            </x-slot>
            <div class="bg-white p-4">
                <ul class="flex h-full max-h-[26rem] flex-col divide-y divide-neutral-300 overflow-y-auto">
                    <li x-data="{ isCategoryFilterExpanded: true }" class="py-4">
                        <button
                            id="responsive-category-accordion-controls"
                            type="button"
                            class="focus-visible:outline-hidden flex w-full items-center justify-between gap-4 rounded-md px-4 py-2 text-left font-medium tracking-tight text-black underline-offset-2 transition-colors hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:underline"
                            :class="isCategoryFilterExpanded ? 'bg-neutral-100' : 'bg-white'"
                            aria-controls="responsive-category-accordion"
                            x-on:click="isCategoryFilterExpanded = ! isCategoryFilterExpanded"
                            x-bind:aria-expanded="isCategoryFilterExpanded ? 'true' : 'false'"
                        >
                            Kategori
                            <svg
                                class="size-4 shrink-0 transition-transform"
                                :class="isCategoryFilterExpanded ? 'rotate-180' : ''"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke-width="2"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div
                            id="responsive-category-accordion"
                            role="region"
                            aria-labelledby="responsive-category-accordion-controls"
                            x-show="isCategoryFilterExpanded"
                            x-collapse
                            x-cloak
                        >
                            <ul class="mt-2 flex flex-col gap-y-2">
                                @foreach ($primaryCategories as $category)
                                    <li x-data="{ isSubcategoryExpanded: false }" class="ms-4">
                                        <button
                                            id="responsive-subcategory-accordion-controls"
                                            type="button"
                                            class="focus-visible:outline-hidden flex w-full items-center justify-between gap-4 rounded-md px-4 py-2 text-left font-medium tracking-tight text-black underline-offset-2 transition-colors hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:underline"
                                            :class="isSubcategoryExpanded ? 'bg-neutral-100' : 'bg-white'"
                                            aria-controls="responsive-subcategory-accordion"
                                            x-on:click="isSubcategoryExpanded = ! isSubcategoryExpanded"
                                            x-bind:aria-expanded="isSubcategoryExpanded ? 'true' : 'false'"
                                        >
                                            {{ ucwords($category->name) }}
                                            <svg
                                                class="size-4 shrink-0 transition-transform"
                                                :class="isSubcategoryExpanded ? 'rotate-180' : ''"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke-width="2"
                                                stroke="currentColor"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                                />
                                            </svg>
                                        </button>
                                        <div
                                            id="responsive-subcategory-accordion"
                                            role="region"
                                            aria-labelledby="responsive-subcategory-accordion-controls"
                                            x-show="isSubcategoryExpanded"
                                            x-collapse
                                            x-cloak
                                        >
                                            <ul class="mt-4 flex flex-col items-start gap-y-4">
                                                @foreach ($category->subcategories as $subcategory)
                                                    <li class="ms-4 inline-flex items-center">
                                                        <x-form.checkbox
                                                            wire:model.lazy="subcategoryFilters"
                                                            id="{{ $subcategory->slug }}-responsive-subcategory-filter"
                                                            name="{{ $subcategory->slug }}-responsive-subcategory-filter"
                                                            value="{{ $subcategory->slug }}"
                                                            :hasError="$errors->has('subcategoryFilters')"
                                                        />
                                                        <label
                                                            for="{{ $subcategory->slug }}-responsive-subcategory-filter"
                                                            class="ms-2 text-base font-medium leading-none tracking-tight text-black"
                                                        >
                                                            {{ ucwords($subcategory->name) }}
                                                        </label>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                    <li x-data="{ isRatingFilterExpanded: false }" class="py-4">
                        <button
                            id="rating-accordion-controls"
                            type="button"
                            class="focus-visible:outline-hidden flex w-full items-center justify-between gap-4 rounded-md px-4 py-2 text-left font-medium tracking-tight text-black underline-offset-2 transition-colors hover:bg-neutral-50 focus-visible:bg-neutral-50 focus-visible:underline"
                            :class="isRatingFilterExpanded ? 'bg-neutral-100' : 'bg-white'"
                            aria-controls="rating-accordion"
                            x-on:click="isRatingFilterExpanded = ! isRatingFilterExpanded"
                            x-bind:aria-expanded="isRatingFilterExpanded ? 'true' : 'false'"
                        >
                            Rating
                            <svg
                                class="size-4 shrink-0 transition-transform"
                                :class="isRatingFilterExpanded ? 'rotate-180' : ''"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke-width="2"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div
                            id="rating-accordion"
                            role="region"
                            aria-labelledby="rating-accordion-controls"
                            x-show="isRatingFilterExpanded"
                            x-collapse
                            x-cloak
                        >
                            <ul class="mt-4 flex flex-col items-start gap-y-4">
                                @for ($rating = 5; $rating > 0; $rating--)
                                    <li class="ms-4 inline-flex items-center">
                                        <x-form.checkbox
                                            wire:model.lazy="ratingFilters"
                                            id="{{ $rating }}-responsive-rating-filter"
                                            name="{{ $rating }}-responsive-rating-filter"
                                            value="{{ $rating }}"
                                            :hasError="$errors->has('ratingFilters')"
                                        />
                                        <label
                                            for="{{ $rating }}-responsive-rating-filter"
                                            class="ms-2 inline-flex items-center gap-x-1 text-base font-medium leading-none tracking-tight text-black"
                                        >
                                            <svg
                                                class="size-4 shrink-0 text-yellow-500"
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
                                            {{ $rating }}
                                        </label>
                                    </li>
                                @endfor
                            </ul>
                        </div>
                    </li>
                </ul>
                <div class="absolute bottom-0 w-[calc(100%-2rem)] border-t border-t-neutral-300 bg-white py-4">
                    <x-common.button
                        variant="secondary"
                        class="w-full"
                        :disabled="empty($subcategoryFilters) && empty($ratingFilters)"
                        wire:click="resetFilter"
                        wire:loading.attr="disabled"
                        wire:target="resetFilter"
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
                            wire:loading.class="animate-spin"
                            wire:target="resetFilter"
                        >
                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                            <path d="M21 3v5h-5" />
                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                            <path d="M8 16H3v5" />
                        </svg>
                        <span wire:loading.remove wire:target="resetFilter">Reset Filter</span>
                        <span wire:loading wire:target="resetFilter">Sedang Diproses...</span>
                    </x-common.button>
                </div>
            </div>
        </x-common.offcanvas>
        <section class="w-full shrink">
            <div class="flex items-center justify-end gap-x-2">
                <label for="sort" class="text-sm font-medium leading-none tracking-tight text-black">
                    Sortir berdasarkan:
                </label>
                <select
                    wire:model.lazy="sortBy"
                    name="sort"
                    id="sort"
                    class="mx-0.5 block w-52 rounded-lg border border-neutral-300 p-2.5 text-sm font-medium tracking-tight text-black focus:border-primary focus:ring-primary disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="" selected>Pilih</option>
                    <option value="nama-asc">Nama A ke Z</option>
                    <option value="nama-desc">Nama Z ke A</option>
                    <option value="harga-asc">Harga Rendah ke Tinggi</option>
                    <option value="harga-desc">Harga Tinggi ke Rendah</option>
                    <option value="diskon">Sedang Diskon</option>
                    <option value="rating-desc">Rating Tertinggi</option>
                    <option value="terbaru">Produk Terbaru</option>
                    <option value="terlaris">Penjualan Terbanyak</option>
                </select>
            </div>
            <div class="mt-4 flex justify-end">
                <x-common.button
                    variant="secondary"
                    class="float-end lg:hidden"
                    x-on:click.prevent.stop="$dispatch('open-offcanvas', 'product-catalog-filter')"
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
            @if (! empty($subcategoryFilters) || ! empty($ratingFilters))
                <ul class="mt-4 flex flex-wrap items-center gap-2">
                    <li class="text-sm font-medium leading-none tracking-tight text-black">Filter:</li>
                    @if (! empty($subcategoryFilters))
                        @foreach ($subcategoryFilters as $subcategoryFilter)
                            <li>
                                <x-common.button
                                    type="button"
                                    variant="secondary"
                                    class="!px-4 !py-2 !text-xs"
                                    wire:click="removeSubcategoryFilter('{{ $subcategoryFilter }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="removeSubcategoryFilter('{{ $subcategoryFilter }}')"
                                >
                                    {{ ucwords(str_replace('-', ' ', $subcategoryFilter)) }}
                                    <svg
                                        class="ms-1 size-3 shrink-0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                        wire:loading.remove
                                        wire:target="removeSubcategoryFilter('{{ $subcategoryFilter }}')"
                                    >
                                        <path d="M18 6 6 18" />
                                        <path d="m6 6 12 12" />
                                    </svg>
                                    <div
                                        class="ms-1 inline-block size-3 animate-spin rounded-full border-[2px] border-current border-t-transparent"
                                        role="status"
                                        aria-label="loading"
                                        wire:loading
                                        wire:target="removeSubcategoryFilter('{{ $subcategoryFilter }}')"
                                    >
                                        <span class="sr-only">Sedang diproses...</span>
                                    </div>
                                </x-common.button>
                            </li>
                        @endforeach
                    @endif

                    @if (! empty($ratingFilters))
                        @foreach ($ratingFilters as $ratingFilter)
                            <li>
                                <x-common.button
                                    type="button"
                                    variant="secondary"
                                    class="!px-4 !py-2 !text-xs"
                                    wire:click="removeRatingFilter('{{ $ratingFilter }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="removeRatingFilter('{{ $ratingFilter }}')"
                                >
                                    <svg
                                        class="size-3 shrink-0 text-yellow-500"
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
                                    {{ ucwords(str_replace('-', ' ', $ratingFilter)) }}
                                    <svg
                                        class="ms-1 size-3 shrink-0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                        wire:loading.remove
                                        wire:target="removeRatingFilter('{{ $ratingFilter }}')"
                                    >
                                        <path d="M18 6 6 18" />
                                        <path d="m6 6 12 12" />
                                    </svg>
                                    <div
                                        class="ms-1 inline-block size-3 animate-spin rounded-full border-[2px] border-current border-t-transparent"
                                        role="status"
                                        aria-label="loading"
                                        wire:loading
                                        wire:target="removeRatingFilter('{{ $ratingFilter }}')"
                                    >
                                        <span class="sr-only">Sedang diproses...</span>
                                    </div>
                                </x-common.button>
                            </li>
                        @endforeach
                    @endif
                </ul>
            @endif

            <div class="relative mt-6 grid grid-cols-2 gap-6 md:grid-cols-3 xl:grid-cols-4">
                @forelse ($this->products as $product)
                    <x-common.product-card
                        :product="$product"
                        wire:key="{{ $product->id }}"
                        wire:loading.class="!pointer-events-none !cursor-not-allowed opacity-50"
                        wire:target="subcategoryFilters,ratingFilters,sortBy,resetFilter"
                    />
                @empty
                    <p
                        class="col-span-2 text-lg font-medium leading-none tracking-tight text-black md:col-span-3 xl:col-span-4"
                    >
                        @if ($this->subcategory !== '')
                            Produk dengan subkategori
                            <strong>"{{ $this->subcategory }}"</strong>
                            tidak ditemukan.
                        @elseif ($this->category !== '')
                            Produk dengan kategori
                            <strong>"{{ $this->category }}"</strong>
                            tidak ditemukan.
                        @elseif ($this->search !== '')
                            Produk dengan nama
                            <strong>"{{ $this->search }}"</strong>
                            tidak ditemukan.
                        @elseif (! empty($this->subcategoryFilters))
                            Produk dengan filter subkategori
                            <strong>"{{ implode(', ', $this->subcategoryFilters) }}"</strong>
                            tidak ditemukan.
                        @elseif (! empty($this->ratingFilters))
                            Produk dengan filter rating
                            <strong>"{{ implode(', ', $this->ratingFilters) }}"</strong>
                            tidak ditemukan.
                        @else
                            Produk tidak ditemukan.
                        @endif
                    </p>
                @endforelse

                <div
                    class="absolute left-1/2 top-20 h-full -translate-x-1/2 md:top-40"
                    wire:loading
                    wire:target="subcategoryFilters,ratingFilters,sortBy,resetFilter"
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
            <div class="mt-4 p-4">
                {{ $this->products->links('components.common.pagination') }}
            </div>
        </section>
    </div>
</div>
