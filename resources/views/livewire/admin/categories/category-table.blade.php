<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    #[Computed]
    public function categories()
    {
        return \App\Models\Category::when($this->search !== '', function ($query) {
            return $query->where('name', 'like', '%' . $this->search . '%');
        })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
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
}; ?>

<div>
    <div class="border-b border-neutral-300 pb-4">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 start-0 z-20 flex items-center ps-3.5">
                <svg
                    class="size-4 shrink-0 text-neutral-600"
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
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
            </div>
            <div class="relative">
                <input
                    wire:model.debounce.live="search"
                    class="block w-full rounded-lg border border-neutral-300 py-2 pe-4 ps-10 text-sm placeholder:text-neutral-600 focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                    type="text"
                    role="combobox"
                    aria-expanded="false"
                    placeholder="Cari data kategori produk berdasarkan nama..."
                />
                <div
                    wire:loading
                    wire:target="search"
                    class="pointer-events-none absolute end-0 top-1/2 -translate-y-1/2 pe-3"
                >
                    <svg
                        class="size-5 animate-spin text-neutral-900"
                        width="16"
                        height="16"
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
                        wire:target="search"
                        type="button"
                        class="absolute end-0 top-1/2 -translate-y-1/2 pe-3"
                    >
                        <svg
                            class="size-5 text-neutral-900"
                            width="16"
                            height="16"
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
    <div class="w-full overflow-hidden overflow-x-auto">
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
                                        'text-neutral-500',
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
                                        'text-neutral-500',
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
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('products_count')"
                        >
                            Total Produk
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-neutral-500',
                                        'text-primary' => $sortField === 'products_count' && $sortDirection === 'desc',
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
                                        'text-neutral-500',
                                        'text-primary' => $sortField === 'products_count' && $sortDirection === 'asc',
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
                            wire:click="sortBy('is_primary')"
                        >
                            Kategori Utama
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-neutral-500',
                                        'text-primary' => $sortField === 'is_primary' && $sortDirection === 'desc',
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
                                        'text-neutral-500',
                                        'text-primary' => $sortField === 'is_primary' && $sortDirection === 'asc',
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
                            wire:click="sortBy('created_at')"
                        >
                            Dibuat Pada
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-neutral-500',
                                        'text-primary' => $sortField === 'created_at' && $sortDirection === 'desc',
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
                                        'text-neutral-500',
                                        'text-primary' => $sortField === 'created_at' && $sortDirection === 'asc',
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
                @forelse ($this->categories as $category)
                    <tr wire:key="{{ $category->id }}" wire:loading.class="opacity-50">
                        <td class="p-4 font-normal tracking-tight text-black/80" align="left">
                            {{ $loop->index + 1 . '.' }}
                        </td>
                        <td class="p-4 font-medium tracking-tight text-black" align="left">
                            {{ ucfirst($category->name) }}
                        </td>
                        <td class="p-4 font-normal tracking-tight text-black/80" align="center">100</td>
                        <td class="p-4" align="center">
                            @if ($category->is_primary)
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                                    Ya
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium tracking-tight text-neutral-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-neutral-800"></span>
                                    Tidak
                                </span>
                            @endif
                        </td>
                        <td class="p-4 font-normal tracking-tight text-black/80" align="center">
                            {{ formatTimestamp($category->created_at) }}
                        </td>
                        <td class="relative px-4 py-2" align="right">
                            <x-common.dropdown width="48">
                                <x-slot name="trigger">
                                    <button type="button" class="rounded-full p-2 text-black hover:bg-neutral-100">
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
                                    @can('view category details')
                                        <x-common.dropdown-link
                                            :href="route('admin.categories.show', ['slug' => $category->slug])"
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
                                    @endcan

                                    @can('edit categories')
                                        <x-common.dropdown-link
                                            :href="route('admin.categories.edit', ['slug' => $category->slug])"
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

                                    @can('delete categories')
                                        <x-common.dropdown-link
                                            x-on:click.prevent="$dispatch('open-modal', 'confirm-category-deletion-{{ $category->id }}')"
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
                                        @push('overlays')
                                            <x-common.modal
                                                name="confirm-category-deletion-{{ $category->id }}"
                                                :show="$errors->isNotEmpty()"
                                                focusable
                                            >
                                                <form
                                                    action="{{ route('admin.categories.destroy', ['category' => $category]) }}"
                                                    method="POST"
                                                    class="flex flex-col items-center p-6"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="mb-4 rounded-full bg-red-100 p-4" aria-hidden="true">
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
                                                    <h2 class="mb-2 text-black">
                                                        Hapus Kategori {{ ucwords($category->name) }}
                                                    </h2>
                                                    <p
                                                        class="mb-8 text-center text-base font-medium tracking-tight text-black/70"
                                                    >
                                                        Apakah anda yakin ingin menghapus kategori
                                                        <strong>"{{ strtolower($category->name) }}"</strong>
                                                        ini ? Proses ini tidak dapat dibatalkan, seluruh data yang
                                                        terkait dengan kategori ini akan dihapus dari sistem.
                                                    </p>
                                                    <div class="flex justify-end gap-4">
                                                        <x-common.button
                                                            variant="secondary"
                                                            x-on:click="$dispatch('close')"
                                                        >
                                                            Batal
                                                        </x-common.button>
                                                        <x-common.button type="submit" variant="danger">
                                                            Hapus Kategori
                                                        </x-common.button>
                                                    </div>
                                                </form>
                                            </x-common.modal>
                                        @endpush
                                    @endcan
                                </x-slot>
                            </x-common.dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="p-4" colspan="6">Data kategori tidak ditemukan</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $this->categories->links() }}
</div>
