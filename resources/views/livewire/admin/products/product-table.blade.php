<?php

use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithoutUrlPagination;

    #[Url(as: 'pencarian', except: '')]
    public string $search = '';

    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public int $perPage = 10;

    #[Locked]
    public bool $archived = false;

    public function mount(bool $archived = false): void
    {
        $this->archived = $archived;
    }

    /**
     * Lazy loading that displays the table skeleton with dynamic table rows.
     */
    public function placeholder(): View
    {
        $totalRows = 8;

        return view('components.skeleton.table', compact('totalRows'));
    }

    /**
     * Get a paginated list of products with thumbnail, category, and subcategory.
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::queryAllWithRelations(
            columns: [
                'products.id',
                'products.name',
                'products.slug',
                'products.main_sku',
                'products.base_price',
                'products.base_price_discount',
                'products.is_active',
                'products.updated_at',
                'products.deleted_at',
            ],
            relations: ['thumbnail', 'category', 'aggregates'],
        )
            ->when(! $this->archived, function ($query) {
                return $query->whereNull('products.deleted_at');
            })
            ->when($this->archived, function ($query) {
                return $query->whereNotNull('products.deleted_at');
            })
            ->when($this->search !== '', function ($query) {
                return $query->where('products.name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * Reset the search query.
     */
    public function resetSearch(): void
    {
        $this->reset('search');
    }

    /**
     * Sort the products by the specified field.
     */
    public function sortBy($field): void
    {
        if (
            ! in_array($field, [
                'name',
                'main_sku',
                'category_name',
                'base_price',
                'total_stock',
                'is_active',
                'total_sold',
                'updated_at',
            ])
        ) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    /**
     * Change product status to active or inactive.
     *
     * @param   string  $id - The ID of the product to update.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to update the product status.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function changeStatus(string $id)
    {
        if ($this->archived) {
            return;
        }

        $product = (new Product())->newFromBuilder(
            Product::queryById(id: $id, columns: ['id', 'name', 'is_active'])->first(),
        );

        if (! $product) {
            session()->flash('error', 'Produk tidak ditemukan.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        }

        try {
            $this->authorize('update', $product);

            DB::transaction(function () use ($product) {
                $product->update([
                    'is_active' => $product->is_active ? false : true,
                ]);
            });

            $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

            session()->flash('success', 'Produk ' . $product->name . ' berhasil ' . $status . '.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
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
                    'operation' => 'Updating product status data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah status produk ' .
                    $product->name .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Updating product status data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        }
    }

    /**
     * Archive product data (soft delete).
     *
     * @param   string  $id - The ID of the product to archive.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to archive the product.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function archive(string $id)
    {
        if ($this->archived) {
            return;
        }

        $product = (new Product())->newFromBuilder(Product::queryById(id: $id, columns: ['id', 'name'])->first());

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
                    'operation' => 'Soft deleting product data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengarsip produk ' . $product->name . ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Soft deleting product data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        }
    }

    /**
     * Restore product data.
     *
     * @param   string  $id - The ID of the product to restore.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to restore the product.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function restore(string $id)
    {
        if (! $this->archived) {
            return;
        }

        $product = (new Product())->newFromBuilder(
            Product::queryById(id: $id, columns: ['id', 'name'])
                ->whereNotNull('deleted_at')
                ->first(),
        );

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
                    'operation' => 'Restoring product data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam memulihkan produk ' . $product->name . ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Restoring product data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        }
    }

    /**
     * Force delete product data.
     *
     * @param   string  $id - The ID of the product to force delete.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to force delete the product.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function delete(string $id)
    {
        if (! $this->archived) {
            return;
        }

        $product = (new Product())->newFromBuilder(
            Product::queryById(id: $id, columns: ['id', 'name'])
                ->whereNotNull('deleted_at')
                ->first(),
        );

        if (! $product) {
            session()->flash('error', 'Produk tidak ditemukan.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        }

        $totalSold = (int) $product::getAggregates(productId: $product->id)->total_sold;

        if ($totalSold > 0) {
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
                    'operation' => 'Force deleting product data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menghapus produk ' . $productName . ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Force deleting product data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.archived-products.index'), navigate: true);
        }
    }
}; ?>

<div>
    <x-datatable.table searchable="produk">
        <x-slot name="head">
            <x-datatable.row>
                <x-datatable.heading align="center">No.</x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-72"
                    :direction="$sortField === 'name' ? $sortDirection : null "
                    wire:click="sortBy('name')"
                    align="left"
                >
                    Nama
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-56"
                    :direction="$sortField === 'main_sku' ? $sortDirection : null "
                    wire:click="sortBy('main_sku')"
                    align="left"
                >
                    SKU Utama
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-52"
                    :direction="$sortField === 'category_name' ? $sortDirection : null "
                    wire:click="sortBy('category_name')"
                    align="left"
                >
                    Kategori / Subkategori
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-36"
                    :direction="$sortField === 'base_price' ? $sortDirection : null "
                    wire:click="sortBy('base_price')"
                    align="center"
                >
                    Harga
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-32"
                    :direction="$sortField === 'total_stock' ? $sortDirection : null "
                    wire:click="sortBy('total_stock')"
                    align="center"
                >
                    Total Stok
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-32"
                    :direction="$sortField === 'is_active' ? $sortDirection : null "
                    wire:click="sortBy('is_active')"
                    align="center"
                >
                    Status
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-36"
                    :direction="$sortField === 'total_sold' ? $sortDirection : null "
                    wire:click="sortBy('total_sold')"
                    align="center"
                >
                    Total Terjual
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-56"
                    :direction="$sortField === 'updated_at' ? $sortDirection : null "
                    wire:click="sortBy('updated_at')"
                    align="left"
                >
                    Terakhir Diubah Pada
                </x-datatable.heading>
                <x-datatable.heading class="px-4 py-2"></x-datatable.heading>
            </x-datatable.row>
        </x-slot>
        <x-slot name="body">
            @forelse ($this->products as $product)
                <x-datatable.row
                    valign="middle"
                    wire:key="{{ $product->id }}"
                    wire:loading.class="opacity-50"
                    wire:target="search,sortBy,resetSearch,perPage"
                >
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="center">
                        {{ $loop->iteration . '.' }}
                    </x-datatable.cell>
                    <x-datatable.cell class="flex h-full w-full items-center gap-x-4" align="left">
                        @if ($product->thumbnail)
                            <div class="flex h-full shrink-0 items-center">
                                <div
                                    class="aspect-square size-16 shrink-0 overflow-hidden rounded-md border border-neutral-300"
                                >
                                    <img
                                        src="{{ asset('storage/uploads/product-images/' . $product->thumbnail) }}"
                                        class="h-full w-full object-cover"
                                        alt="Gambar utama produk {{ $product->name }}"
                                        loading="lazy"
                                    />
                                </div>
                            </div>
                        @else
                            <div class="flex h-full shrink-0 items-center">
                                <div
                                    class="flex aspect-square size-16 shrink-0 items-center justify-center rounded-md bg-neutral-200"
                                >
                                    <x-common.application-logo class="size-8 text-black opacity-50" />
                                </div>
                            </div>
                        @endif

                        <span
                            class="whitespace-normal text-pretty break-words text-sm font-medium tracking-tight text-black"
                        >
                            {{ ucwords($product->name) }}
                        </span>
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="left">
                        {{ $product->main_sku }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="left">
                        @if ($product->category_name && $product->subcategory_name)
                            {{ ucwords($product->category_name) . ' / ' . ucwords($product->subcategory_name) }}
                        @else
                            -
                        @endif
                    </x-datatable.cell>
                    <x-datatable.cell class="h-full align-middle" align="center">
                        <div
                            class="flex h-full flex-col items-stretch gap-y-1 text-sm font-normal tracking-tight text-black/70"
                        >
                            @if ($product->total_variants > 1)
                                <span>Mulai dari</span>
                            @endif

                            @if ($product->base_price_discount)
                                Rp {{ formatPrice($product->base_price_discount) }}
                                <del class="text-xs text-black/50">Rp {{ formatPrice($product->base_price) }}</del>
                            @else
                                Rp {{ formatPrice($product->base_price) }}
                            @endif
                        </div>
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="center">
                        {{ $product->total_stock ? formatPrice($product->total_stock) : 0 }}
                    </x-datatable.cell>
                    <x-datatable.cell align="center">
                        @if ($product->deleted_at)
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
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="center">
                        {{ $product->total_sold ? formatPrice($product->total_sold) : 0 }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="left">
                        {{ formatTimestamp($product->updated_at) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="relative" align="right">
                        <x-common.dropdown width="48">
                            <x-slot name="trigger">
                                <button
                                    type="button"
                                    class="rounded-full p-2 text-black hover:bg-neutral-100"
                                    aria-label="Buka menu untuk produk {{ preg_replace('/[^A-Za-z0-9 ]/', '', strtolower($product->name)) }}"
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
                                @if (! $this->archived)
                                    <x-common.dropdown-link
                                        :href="route('admin.products.show', ['slug' => $product->slug])"
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
                                            aria-hidden="true"
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
                                            type="button"
                                            wire:click="changeStatus('{{ $product->id }}')"
                                            x-on:click="event.stopPropagation()"
                                            wire:loading.class="!cursor-not-allowed opacity-50"
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
                                                aria-hidden="true"
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
                                                aria-hidden="true"
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
                                            x-on:click.prevent="$dispatch('open-modal', 'confirm-product-archivation-{{ $product->id }}')"
                                            class="text-red-500 hover:bg-red-50"
                                        >
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
                                            >
                                                <rect width="20" height="5" x="2" y="3" rx="1" />
                                                <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                                                <path d="M10 12h4" />
                                            </svg>
                                            Arsipkan
                                        </x-common.dropdown-link>
                                        <template x-teleport="body">
                                            <x-common.modal
                                                name="confirm-product-archivation-{{ $product->id }}"
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
                                                            aria-hidden="true"
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
                                                        class="mb-8 text-center text-base font-normal tracking-tight text-black/70"
                                                    >
                                                        Apakah anda yakin ingin mengarsip produk
                                                        <strong>"{{ strtolower($product->name) }}"</strong>
                                                        ini ? Produk yang diarsip tidak akan dapat dilihat maupun dibeli
                                                        oleh pelanggan. Anda dapat melihat produk yang di arsip pada
                                                        menu
                                                        <a
                                                            href="{{ route('admin.archived-products.index') }}"
                                                            class="underline"
                                                            wire:navigate
                                                        >
                                                            arsip produk
                                                        </a>
                                                        .
                                                    </p>
                                                    <div
                                                        class="flex w-full flex-col items-center justify-end gap-4 md:flex-row"
                                                    >
                                                        <x-common.button
                                                            variant="secondary"
                                                            class="w-full md:w-fit"
                                                            x-on:click="$dispatch('close')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="archive('{{ $product->id }}')"
                                                        >
                                                            Batal
                                                        </x-common.button>
                                                        <x-common.button
                                                            variant="danger"
                                                            class="w-full md:w-fit"
                                                            wire:click="archive('{{ $product->id }}')"
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
                                @else
                                    @can('restore products')
                                        <x-common.dropdown-link
                                            type="button"
                                            wire:click="restore('{{ $product->id }}')"
                                            x-on:click="event.stopPropagation()"
                                            wire:loading.class="!cursor-not-allowed opacity-50"
                                            wire:target="restore('{{ $product->id }}')"
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
                                                aria-hidden="true"
                                            >
                                                <rect width="20" height="5" x="2" y="3" rx="1" />
                                                <path d="M4 8v11a2 2 0 0 0 2 2h2" />
                                                <path d="M20 8v11a2 2 0 0 1-2 2h-2" />
                                                <path d="m9 15 3-3 3 3" />
                                                <path d="M12 12v9" />
                                            </svg>
                                            <span wire:loading.remove wire:target="restore('{{ $product->id }}')">
                                                Pulihkan
                                            </span>
                                            <span wire:loading wire:target="restore('{{ $product->id }}')">
                                                Sedang diproses
                                            </span>
                                        </x-common.dropdown-link>
                                    @endcan

                                    @if ($product->total_sold <= 0)
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
                                                    aria-hidden="true"
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
                                                                aria-hidden="true"
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
                                                            class="mb-8 text-center text-base font-normal tracking-tight text-black/70"
                                                        >
                                                            Apakah anda yakin ingin menghapus produk
                                                            <strong>"{{ strtolower($product->name) }}"</strong>
                                                            ini ? Proses ini tidak dapat dibatalkan, seluruh data yang
                                                            terkait dengan produk ini akan dihapus dari sistem.
                                                        </p>
                                                        <div
                                                            class="flex w-full flex-col items-center justify-end gap-4 md:flex-row"
                                                        >
                                                            <x-common.button
                                                                variant="secondary"
                                                                class="w-full md:w-fit"
                                                                x-on:click="$dispatch('close')"
                                                                wire:loading.attr="disabled"
                                                                wire:target="delete('{{ $product->id }}')"
                                                            >
                                                                Batal
                                                            </x-common.button>
                                                            <x-common.button
                                                                variant="danger"
                                                                class="w-full md:w-fit"
                                                                wire:click="delete('{{ $product->id }}')"
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
                                @endif
                            </x-slot>
                        </x-common.dropdown>
                    </x-datatable.cell>
                </x-datatable.row>
            @empty
                <tr wire:loading.class="opacity-50" wire:target="search,sortBy,resetSearch,perPage">
                    <td class="p-4" colspan="10" align="center">
                        <figure class="my-4 flex h-full flex-col items-center justify-center">
                            <img
                                src="https://placehold.co/400"
                                class="mb-6 size-72 object-cover"
                                alt="Gambar ilustrasi produk tidak ditemukan"
                            />
                            <figcaption class="flex flex-col items-center">
                                <h2 class="mb-3 text-center !text-2xl text-black">Produk Tidak Ditemukan</h2>
                                <p class="text-center text-base font-normal tracking-tight text-black/70">
                                    @if ($archived)
                                        Seluruh produk Anda yang diarsipkan akan ditampilkan di halaman ini.
                                    @else
                                        @if ($search)
                                            Data produk dengan nama
                                            <strong>"{{ $search }}"</strong>
                                            tidak ditemukan, silakan coba untuk mengubah kata kunci pencarian Anda.
                                        @else
                                            Seluruh produk Anda akan ditampilkan di halaman ini. Anda dapat menambahkan
                                            produk baru dengan menekan tombol
                                            <strong>tambah</strong>
                                            diatas.
                                        @endif
                                    @endif
                                </p>
                            </figcaption>
                        </figure>
                    </td>
                </tr>
            @endforelse
        </x-slot>
        <x-slot name="loader">
            <div
                class="absolute left-1/2 top-[50%-1rem] h-full -translate-x-1/2 -translate-y-1/2"
                wire:loading
                wire:target="search,sortBy,resetSearch,perPage"
            >
                <div
                    class="inline-block size-10 animate-spin rounded-full border-4 border-current border-t-transparent text-primary"
                    role="status"
                    aria-label="loading"
                >
                    <span class="sr-only">Sedang diproses...</span>
                </div>
            </div>
        </x-slot>
        <x-slot name="pagination">
            {{ $this->products->links('components.common.pagination') }}
        </x-slot>
    </x-datatable.table>
</div>
