<?php

use App\Models\Order;
use App\Services\DocumentService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    protected DocumentService $documentService;

    public string $year = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public string $sortField = 'order_number';
    public string $sortDirection = 'asc';

    public function boot(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    #[Computed]
    public function sales()
    {
        return Order::where('status', 'completed')
            ->when($this->search !== '', function ($query) {
                return $query->where('order_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->year !== '', function ($query) {
                return $query->whereYear('created_at', $this->year);
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

    public function download()
    {
        $sales = Order::where('status', 'completed')
            ->when($this->search !== '', function ($query) {
                return $query->where('order_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->year !== '', function ($query) {
                return $query->whereYear('created_at', $this->year);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        if ($sales->isEmpty()) {
            return;
        }

        return $this->documentService->generateSalesReport($sales, $this->year);
    }
}; ?>

<div>
    <div class="flex items-center justify-end gap-x-4 pb-4">
        <div class="inline-flex items-center gap-x-2">
            <x-form.input-label for="sales-year" value="Tahun Penjualan :" :required="false" />
            <select
                wire:model.lazy="year"
                id="sales-year"
                name="sales-year"
                class="block w-32 rounded-md border border-neutral-300 px-4 py-3 text-sm text-black focus:border-primary focus:ring-primary"
            >
                <option value="">Pilih tahun</option>
                <option value="{{ date('Y') }}">{{ date('Y') }}</option>
                <option value="2026">2026</option>
            </select>
        </div>
        <x-common.button variant="primary" wire:click="download" :disabled="empty($this->sales)">
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
                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                <path d="M12 18v-6" />
                <path d="m9 15 3 3 3-3" />
            </svg>
            Unduh Laporan
        </x-common.button>
    </div>
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
                    id="sale-search"
                    name="sale-search"
                    wire:model.live.debounce.250ms="search"
                    class="block w-full ps-10"
                    type="text"
                    role="combobox"
                    placeholder="Cari data penjualan berdasarkan nomor pesanan..."
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
                            wire:click="sortBy('order_number')"
                        >
                            Nomor Pesanan
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'order_number' && $sortDirection === 'desc',
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
                                        'text-primary' => $sortField === 'order_number' && $sortDirection === 'asc',
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
                            wire:click="sortBy('order_number')"
                        >
                            Nama Pembeli
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'order_number' && $sortDirection === 'desc',
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
                                        'text-primary' => $sortField === 'order_number' && $sortDirection === 'asc',
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
                        <div
                            class="flex items-center justify-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                        >
                            Status Pesanan
                        </div>
                    </th>
                    <th scope="col" align="center">
                        <button
                            type="button"
                            class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black"
                            wire:click="sortBy('total_amount')"
                        >
                            Total
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'total_amount' && $sortDirection === 'desc',
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
                                        'text-primary' => $sortField === 'total_amount' && $sortDirection === 'asc',
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
                            wire:click="sortBy('method')"
                        >
                            Metode Pembayaran
                            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                                <rect width="256" height="256" fill="none" />
                                <polyline
                                    @class([
                                        'text-black/70',
                                        'text-primary' => $sortField === 'method' && $sortDirection === 'desc',
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
                                        'text-primary' => $sortField === 'method' && $sortDirection === 'asc',
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
                            wire:click="sortBy('created_at')"
                        >
                            Tanggal Pesanan Dibuat
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
            <tbody>
                @forelse ($this->sales as $sale)
                    <tr
                        wire:key="{{ $sale->id }}"
                        wire:loading.class="opacity-50"
                        wire:target="search,sortBy,resetSearch,year"
                    >
                        <td class="p-4 font-normal tracking-tight text-black/70" align="left">
                            {{ $loop->index + 1 . '.' }}
                        </td>
                        <td class="min-w-52 p-4 font-medium tracking-tight text-black">{{ $sale->order_number }}</td>
                        <td class="min-w-40 p-4 font-normal tracking-tight text-black/70">{{ $sale->user->name }}</td>
                        <td class="min-w-32 p-4" align="center">
                            <span
                                class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium tracking-tight text-teal-800"
                            >
                                Sukses
                            </span>
                        </td>
                        <td class="min-w-40 p-4 font-normal tracking-tight text-black/70" align="center">
                            Rp {{ formatPrice($sale->total_amount) }}
                        </td>
                        <td class="min-w-40 p-4 font-normal tracking-tight text-black/70" align="center">
                            @if (str_contains($sale->payment->method, 'bank_transfer'))
                                {{ strtoupper(str_replace('bank_transfer_', '', $sale->payment->method)) }}
                            @else
                                {{ strtoupper(str_replace('ewallet_', '', $sale->payment->method)) }}
                            @endif
                        </td>
                        <td class="min-w-40 p-4 font-normal tracking-tight text-black/70">
                            {{ formatTimestamp($sale->created_at) }}
                        </td>
                        <td class="relative px-4 py-2" align="right">
                            <x-common.dropdown width="60">
                                <x-slot name="trigger">
                                    <button
                                        type="button"
                                        class="rounded-full p-2 text-black hover:bg-neutral-100 disabled:hover:bg-white"
                                        wire:loading.attr="disabled"
                                        wire:target="search,sortBy,resetSearch,year"
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
                                    <x-common.dropdown-link
                                        :href="route('admin.orders.show', ['orderNumber' => $sale->order_number])"
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
                                        Detail Pesanan
                                    </x-common.dropdown-link>
                                </x-slot>
                            </x-common.dropdown>
                        </td>
                    </tr>
                @empty
                    <tr wire:loading.class="opacity-50" wire:target="search,sortBy,resetSearch,year">
                        <td class="p-4" colspan="9">
                            <figure class="my-4 flex h-full flex-col items-center justify-center">
                                <img
                                    src="https://placehold.co/400"
                                    class="mb-6 size-72 object-cover"
                                    alt="Gambar ilustrasi data penjualan tidak ditemukan"
                                />
                                <figcaption class="flex flex-col items-center">
                                    <h2 class="mb-3 text-center !text-2xl text-black">
                                        Data Penjualan Tidak Ditemukan
                                    </h2>
                                    <p class="text-center text-base font-normal tracking-tight text-black/70">
                                        @if ($search)
                                            Data penjualan yang Anda cari tidak ditemukan, silakan coba untuk mengubah kata kunci
                                        pencarian Anda.
                                        @elseif ($year)
                                            Data penjualan pada tahun {{ $year }} tidak ditemukan, silakan coba untuk
                                            mengubah tahun penjualan Anda.
                                        @else
                                                Seluruh data penjualan Anda akan ditampilkan di halaman ini.
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
            wire:target="search,sortBy,resetSearch,year"
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
    {{ $this->sales->links() }}
</div>
