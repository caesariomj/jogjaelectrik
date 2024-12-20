<?php

use App\Models\Subcategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
    public function subcategories()
    {
        return Subcategory::with('category')
            ->withCount('products')
            ->when($this->search !== '', function ($query) {
                return $query
                    ->where('subcategories.name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('category', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->when(
                $this->sortField === 'category_name',
                function ($query) {
                    return $query
                        ->join('categories', 'subcategories.category_id', '=', 'categories.id')
                        ->orderBy('categories.name', $this->sortDirection)
                        ->select('subcategories.*');
                },
                function ($query) {
                    return $query->orderBy($this->sortField, $this->sortDirection);
                },
            )
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

    public function delete(string $id)
    {
        $subcategory = Subcategory::find($id);

        if (! $subcategory) {
            session()->flash('error', 'Subkategori tidak ditemukan.');
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
        }

        $subcategoryName = $subcategory->name;

        try {
            $this->authorize('delete', $subcategory);

            DB::transaction(function () use ($subcategory) {
                $subcategory->delete();
            });

            session()->flash('success', 'Subkategori ' . $subcategoryName . ' berhasil dihapus.');
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database error during subcategory deletion: ' . $e->getMessage());

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menghapus subkategori ' .
                    $subcategoryName .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected subcategory deletion error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
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
                    id="subcategory-search"
                    name="subcategory-search"
                    wire:model.live.debounce.250ms="search"
                    class="block w-full ps-10"
                    type="text"
                    role="combobox"
                    placeholder="Cari data subkategori berdasarkan nama atau nama kategori..."
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
                            wire:click="sortBy('category_name')"
                        >
                            Kategori Terkait
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'category_name' && $sortDirection === 'desc',
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
                                        'text-primary' => $sortField === 'category_name' && $sortDirection === 'asc',
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
                                        'text-black/70',
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
                                        'text-black/70',
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
                            wire:click="sortBy('created_at')"
                        >
                            Dibuat Pada
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
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
                                        'text-black/70',
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
                @forelse ($this->subcategories as $subcategory)
                    <tr wire:key="{{ $subcategory->id }}" wire:loading.class="opacity-50">
                        <td class="p-4 font-normal tracking-tight text-black/70" align="left">
                            {{ $loop->index + 1 . '.' }}
                        </td>
                        <td class="min-w-56 p-4 font-medium tracking-tight text-black" align="left">
                            {{ ucfirst($subcategory->name) }}
                        </td>
                        <td class="min-w-56 p-4 font-normal tracking-tight text-black/70" align="left">
                            {{ ucwords($subcategory->category->name) }}
                        </td>
                        <td class="min-w-36 p-4 font-normal tracking-tight text-black/70" align="center">
                            {{ $subcategory->products_count }}
                        </td>
                        <td class="min-w-56 p-4 font-normal tracking-tight text-black/70" align="center">
                            {{ formatTimestamp($subcategory->created_at) }}
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
                                    @can('view subcategory details')
                                        <x-common.dropdown-link
                                            :href="route('admin.subcategories.show', ['slug' => $subcategory->slug])"
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

                                    @can('edit subcategories')
                                        <x-common.dropdown-link
                                            :href="route('admin.subcategories.edit', ['slug' => $subcategory->slug])"
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

                                    @can('delete subcategories')
                                        <x-common.dropdown-link
                                            x-on:click.prevent="$dispatch('open-modal', 'confirm-subcategory-deletion-{{ $subcategory->id }}')"
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
                                                name="confirm-subcategory-deletion-{{ $subcategory->id }}"
                                                :show="$errors->isNotEmpty()"
                                                focusable
                                            >
                                                <div class="flex flex-col items-center p-6">
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
                                                        Hapus Subkategori {{ ucwords($subcategory->name) }}
                                                    </h2>
                                                    <p
                                                        class="mb-8 text-center text-base font-medium tracking-tight text-black/70"
                                                    >
                                                        Apakah anda yakin ingin menghapus subkategori
                                                        <strong>"{{ strtolower($subcategory->name) }}"</strong>
                                                        ini ? Proses ini tidak dapat dibatalkan, seluruh data yang
                                                        terkait dengan subkategori ini akan dihapus dari sistem.
                                                    </p>
                                                    <div class="flex justify-end gap-4">
                                                        <x-common.button
                                                            variant="secondary"
                                                            x-on:click="$dispatch('close')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="delete('{{ $subcategory->id }}')"
                                                        >
                                                            Batal
                                                        </x-common.button>
                                                        <x-common.button
                                                            variant="danger"
                                                            wire:click="delete('{{ $subcategory->id }}')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="delete('{{ $subcategory->id }}')"
                                                        >
                                                            <span
                                                                wire:loading.remove
                                                                wire:target="delete('{{ $subcategory->id }}')"
                                                            >
                                                                Hapus Subkategori
                                                            </span>
                                                            <span
                                                                wire:loading.flex
                                                                wire:target="delete('{{ $subcategory->id }}')"
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
                                </x-slot>
                            </x-common.dropdown>
                        </td>
                    </tr>
                @empty
                    <tr wire:loading.class="opacity-50" wire:target="search,sortBy,resetSearch">
                        <td class="p-4" colspan="6">
                            <figure class="my-4 flex h-full flex-col items-center justify-center">
                                <img
                                    src="https://placehold.co/400"
                                    class="mb-6 size-72 object-cover"
                                    alt="Gambar ilustrasi subkategori tidak ditemukan"
                                />
                                <figcaption class="flex flex-col items-center">
                                    <h2 class="mb-3 text-center !text-2xl text-black">Subkategori Tidak Ditemukan</h2>
                                    <p class="text-center text-base font-normal tracking-tight text-black/70">
                                        @if ($search)
                                            Subkategori yang Anda cari tidak ditemukan, silakan coba untuk mengubah kata kunci
                                        pencarian Anda.
                                        @else
                                            Seluruh kategori Anda akan ditampilkan di halaman ini. Anda dapat
                                            menambahkan kategori baru dengan menekan tombol
                                            <strong>Tambah</strong>
                                            diatas.
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
    {{ $this->subcategories->links() }}
</div>
