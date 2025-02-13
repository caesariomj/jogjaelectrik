<?php

use App\Models\Discount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
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

    /**
     * Lazy loading that displays the table skeleton with dynamic table rows.
     */
    public function placeholder(): View
    {
        $totalRows = 9;

        return view('components.skeleton.table', compact('totalRows'));
    }

    /**
     * Get a paginated list of discounts.
     */
    #[Computed]
    public function discounts(): LengthAwarePaginator
    {
        return Discount::baseQuery(
            columns: [
                'discounts.id',
                'discounts.name',
                'discounts.code',
                'discounts.type',
                'discounts.value',
                'discounts.start_date',
                'discounts.end_date',
                'discounts.usage_limit',
                'discounts.used_count',
                'discounts.max_discount_amount',
                'discounts.minimum_purchase',
                'discounts.is_active',
            ],
        )
            ->when($this->search !== '', function ($query) {
                $query
                    ->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    /**
     * Reset the search query.
     */
    public function resetSearch(): void
    {
        $this->reset('search');
    }

    /**
     * Sort the discounts by the specified field.
     */
    public function sortBy($field): void
    {
        if (! in_array($field, ['name', 'code', 'type', 'value', 'is_active', 'start_date', 'used_count'])) {
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
     * Reset discount usage to 0.
     *
     * @param   string  $id - The ID of the discount to reset the discount usage.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to reset the discount usage.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function resetUsage(string $id)
    {
        $discount = (new Discount())->newFromBuilder(Discount::queryById($id)->first());

        if (! $discount) {
            session()->flash('error', 'Diskon tidak ditemukan.');
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        }

        if (($discount->end_date && $discount->end_date < now()->toDateString()) || $discount->used_count == 0) {
            session()->flash('error', 'Diskon tidak dapat di-reset karena sudah kedaluarsa / belum digunakan.');
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        }

        try {
            $this->authorize('manage', $discount);

            DB::transaction(function () use ($discount) {
                $discount->update([
                    'used_count' => 0,
                ]);
            });

            session()->flash('success', 'Penggunaan diskon ' . $discount->name . ' berhasil di-reset.');
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
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
                    'operation' => 'Reseting discount usage',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam me-reset penggunaan diskon ' .
                    $discount->name .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Reseting discount usage',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        }
    }

    /**
     * Delete discount data.
     *
     * @param   string  $id - The ID of the discount to delete.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to delete the discount.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function delete(string $id)
    {
        $discount = (new Discount())->newFromBuilder(Discount::queryById($id)->first());

        if (! $discount) {
            session()->flash('error', 'Diskon tidak ditemukan.');
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        }

        $discountName = $discount->name;

        try {
            $this->authorize('delete', $discount);

            DB::transaction(function () use ($discount) {
                $discount->delete();
            });

            session()->flash('success', 'Diskon ' . $discountName . ' berhasil dihapus.');
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
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
                    'operation' => 'Deleting discount data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menghapus diskon ' . $discountName . ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Deleting discount data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        }
    }
}; ?>

<div>
    <x-datatable.table searchable="diskon">
        <x-slot name="head">
            <x-datatable.row>
                <x-datatable.heading align="center">No.</x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-40"
                    :direction="$sortField === 'name' ? $sortDirection : null "
                    wire:click="sortBy('name')"
                    align="left"
                >
                    Nama
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-40"
                    :direction="$sortField === 'code' ? $sortDirection : null "
                    wire:click="sortBy('code')"
                    align="left"
                >
                    Kode
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-28"
                    :direction="$sortField === 'type' ? $sortDirection : null "
                    wire:click="sortBy('type')"
                    align="center"
                >
                    Tipe
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-44"
                    :direction="$sortField === 'value' ? $sortDirection : null "
                    wire:click="sortBy('value')"
                    align="center"
                >
                    Potongan Harga
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-32 justify-center"
                    :direction="$sortField === 'is_active' ? $sortDirection : null "
                    wire:click="sortBy('is_active')"
                    align="center"
                >
                    Status
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-72"
                    :direction="$sortField === 'start_date' ? $sortDirection : null "
                    wire:click="sortBy('start_date')"
                    align="left"
                >
                    Periode
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-44"
                    :direction="$sortField === 'used_count' ? $sortDirection : null "
                    wire:click="sortBy('used_count')"
                    align="center"
                >
                    Total Penggunaan
                </x-datatable.heading>
                <x-datatable.heading class="px-4 py-2"></x-datatable.heading>
            </x-datatable.row>
        </x-slot>
        <x-slot name="body">
            @forelse ($this->discounts as $discount)
                <x-datatable.row
                    wire:key="{{ $discount->id }}"
                    wire:loading.class="opacity-50"
                    wire:target="search,sortBy,resetSearch,perPage"
                >
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="center">
                        {{ $loop->iteration . '.' }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-medium tracking-tight text-black" align="left">
                        {{ ucwords($discount->name) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="left">
                        {{ $discount->code }}
                    </x-datatable.cell>
                    <x-datatable.cell align="center">
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium tracking-tight text-blue-800"
                        >
                            <span class="inline-block size-1 rounded-full bg-blue-800"></span>
                            {{ $discount->type === 'fixed' ? 'Nominal' : 'Persentase' }}
                        </span>
                    </x-datatable.cell>
                    <x-datatable.cell
                        @class([
                            'text-sm font-normal tracking-tight text-black/70',
                            'flex w-full flex-col items-center justify-center text-center gap-y-2' => $discount->type === 'percentage',
                        ])
                        align="center"
                    >
                        @if ($discount->type === 'fixed')
                            Rp
                        @endif

                        {{ formatPrice($discount->value) }}

                        @if ($discount->type === 'percentage')
                            %
                            @if ($discount->max_discount_amount)
                                <small>(Maks: Rp {{ formatPrice($discount->max_discount_amount) }})</small>
                            @endif
                        @endif
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="center">
                        @if ($discount->is_active && (! $discount->end_date || $discount->end_date >= now()->toDateString()))
                            <span
                                class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                            >
                                <span class="inline-block size-1 rounded-full bg-teal-800"></span>
                                Aktif
                            </span>
                        @elseif ($discount->end_date && $discount->end_date < now()->toDateString())
                            <span
                                class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-medium tracking-tight text-red-800"
                            >
                                <span class="inline-block size-1 rounded-full bg-red-800"></span>
                                Kadaluarsa
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-x-1.5 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium tracking-tight text-yellow-800"
                            >
                                <span class="inline-block size-1 rounded-full bg-yellow-800"></span>
                                Non-Aktif
                            </span>
                        @endif
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="left">
                        @if ($discount->start_date && $discount->end_date)
                            {{ formatDate($discount->start_date) . ' - ' . formatDate($discount->end_date) }}
                        @else
                            Periode tidak ditentukan.
                        @endif
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="center">
                        {{ $discount->used_count }} /
                        {{ $discount->usage_limit ?? 'Tidak ada batasan penggunaan.' }}
                    </x-datatable.cell>
                    <x-datatable.cell class="relative" align="right">
                        <x-common.dropdown width="60">
                            <x-slot name="trigger">
                                <button
                                    type="button"
                                    class="rounded-full p-2 text-black hover:bg-neutral-100 disabled:hover:bg-white"
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
                                @can('view discount details')
                                    <x-common.dropdown-link
                                        :href="route('admin.discounts.show', ['code' => $discount->code])"
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

                                @can('edit discounts')
                                    <x-common.dropdown-link
                                        :href="route('admin.discounts.edit', ['code' => $discount->code])"
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

                                @can('manage discount usage')
                                    @if ($discount->usage_limit)
                                        @php
                                            $disabled = ($discount->end_date && $discount->end_date < now()->toDateString()) || $discount->used_count == 0;
                                        @endphp

                                        <x-common.dropdown-link
                                            class="!items-start"
                                            x-on:click.prevent="$dispatch('open-modal', 'reset-discount-usage-{{ $discount->id }}')"
                                            :disabled="$disabled"
                                        >
                                            <svg
                                                class="mt-0.5 size-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                                <path d="M3 3v5h5" />
                                            </svg>
                                            Reset Penggunaan Diskon
                                        </x-common.dropdown-link>
                                        <template x-teleport="body">
                                            <x-common.modal
                                                name="reset-discount-usage-{{ $discount->id }}"
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
                                                    <h2 class="mb-2 text-center text-black">
                                                        Reset Penggunaan Diskon {{ ucwords($discount->name) }}
                                                    </h2>
                                                    <p
                                                        class="mb-8 text-center text-base font-normal tracking-tight text-black/70"
                                                    >
                                                        Apakah anda yakin ingin me-reset penggunaan diskon
                                                        <strong>"{{ strtolower($discount->name) }}"</strong>
                                                        ini ? Aksi ini akan mengembalikan jumlah penggunaan diskon
                                                        menjadi nol dan pengguna dapat menggunakan diskon ini kembali.
                                                    </p>
                                                    <div
                                                        class="flex w-full flex-col items-center justify-end gap-4 md:flex-row"
                                                    >
                                                        <x-common.button
                                                            variant="secondary"
                                                            class="w-full md:w-fit"
                                                            x-on:click="$dispatch('close')"
                                                            wire:loading.class="!pointers-event-nonte !cursor-not-allowed opacity-50"
                                                            wire:target="resetUsage('{{ $discount->id }}')"
                                                        >
                                                            Batal
                                                        </x-common.button>
                                                        <x-common.button
                                                            variant="danger"
                                                            class="w-full md:w-fit"
                                                            wire:click="resetUsage('{{ $discount->id }}')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="resetUsage('{{ $discount->id }}')"
                                                        >
                                                            <span
                                                                wire:loading.remove
                                                                wire:target="resetUsage('{{ $discount->id }}')"
                                                            >
                                                                Reset Penggunaan Diskon
                                                            </span>
                                                            <span
                                                                wire:loading.flex
                                                                wire:target="resetUsage('{{ $discount->id }}')"
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
                                    @endif
                                @endcan

                                @can('delete discounts')
                                    <x-common.dropdown-link
                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-discount-deletion-{{ $discount->id }}')"
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
                                            name="confirm-discount-deletion-{{ $discount->id }}"
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
                                                <h2 class="mb-2 text-center text-black">
                                                    Hapus Diskon {{ ucwords($discount->name) }}
                                                </h2>
                                                <p
                                                    class="mb-8 text-center text-base font-normal tracking-tight text-black/70"
                                                >
                                                    Apakah anda yakin ingin menghapus diskon
                                                    <strong>"{{ strtolower($discount->name) }}"</strong>
                                                    ini ? Proses ini tidak dapat dibatalkan, seluruh data yang terkait
                                                    dengan diskon ini akan dihapus dari sistem.
                                                </p>
                                                <div
                                                    class="flex w-full flex-col items-center justify-end gap-4 md:flex-row"
                                                >
                                                    <x-common.button
                                                        variant="secondary"
                                                        class="w-full md:w-fit"
                                                        x-on:click="$dispatch('close')"
                                                        wire:loading.class="!pointers-event-none !cursor-not-allowed opacity-50"
                                                        wire:target="delete('{{ $discount->id }}')"
                                                    >
                                                        Batal
                                                    </x-common.button>
                                                    <x-common.button
                                                        variant="danger"
                                                        class="w-full md:w-fit"
                                                        wire:click="delete('{{ $discount->id }}')"
                                                        wire:loading.attr="disabled"
                                                        wire:target="delete('{{ $discount->id }}')"
                                                    >
                                                        <span
                                                            wire:loading.remove
                                                            wire:target="delete('{{ $discount->id }}')"
                                                        >
                                                            Hapus Diskon
                                                        </span>
                                                        <span
                                                            wire:loading.flex
                                                            wire:target="delete('{{ $discount->id }}')"
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
                    </x-datatable.cell>
                </x-datatable.row>
            @empty
                <tr wire:loading.class="opacity-50" wire:target="search,sortBy,resetSearch,perPage">
                    <td class="p-4" colspan="6" align="center">
                        <figure class="my-4 flex h-full flex-col items-center justify-center">
                            <img
                                src="https://placehold.co/400"
                                class="mb-6 size-72 object-cover"
                                alt="Gambar ilustrasi diskon tidak ditemukan"
                            />
                            <figcaption class="flex flex-col items-center">
                                <h2 class="mb-3 text-center !text-2xl text-black">Diskon Tidak Ditemukan</h2>
                                <p class="text-center text-base font-normal tracking-tight text-black/70">
                                    @if ($search)
                                        Data diskon dengan nama
                                        <strong>"{{ $search }}"</strong>
                                        tidak ditemukan, silakan coba untuk mengubah kata kunci pencarian Anda.
                                    @else
                                        Seluruh diskon Anda akan ditampilkan di halaman ini. Anda dapat menambahkan
                                        diskon baru dengan menekan tombol
                                        <strong>tambah</strong>
                                        diatas.
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
            {{ $this->discounts->links('components.common.pagination') }}
        </x-slot>
    </x-datatable.table>
</div>
