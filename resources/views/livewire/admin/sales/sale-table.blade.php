<?php

use App\Models\Order;
use App\Services\DocumentService;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithoutUrlPagination;

    protected DocumentService $documentService;

    public array $months = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
    ];

    public string $month = '';
    public string $year = '';

    #[Url(as: 'pencarian', except: '')]
    public string $search = '';

    public string $sortField = 'order_number';
    public string $sortDirection = 'asc';
    public int $perPage = 10;

    public function boot(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function mount()
    {
        $this->month = Carbon::now()->month;
        $this->year = date('Y');
    }

    /**
     * Lazy loading that displays the table skeleton with dynamic table rows.
     */
    public function placeholder(): View
    {
        $totalRows = 8;

        return view('components.skeleton.table', compact('totalRows'));
    }

    #[Computed]
    public function sales()
    {
        return Order::queryAllByStatusWithRelations(
            status: 'completed',
            columns: ['orders.order_number'],
            relations: 'order_details',
        )
            ->when($this->search !== '', function ($query) {
                return $query->where('orders.order_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->month !== '', function ($query) {
                return $query->whereMonth('orders.created_at', $this->month);
            })
            ->when($this->year !== '', function ($query) {
                return $query->whereYear('orders.created_at', $this->year);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
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
        if (
            ! auth()
                ->user()
                ->can('download reports')
        ) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mengunduh laporan penjualan.');

            return redirect()->route('admin.sales.index');
        }

        $this->validate(
            rules: [
                'year' => 'required|digits:4',
            ],
            messages: [
                'year.required' => 'Tahun wajib dipilih.',
                'year.digits' => 'Tahun harus terdiri dari 4 digit.',
            ],
            attributes: [
                'year' => 'Tahun',
            ],
        );

        $sales = Order::queryAllByStatusWithRelations(
            status: 'completed',
            columns: ['orders.order_number'],
            relations: 'order_details',
        )
            ->when($this->search !== '', function ($query) {
                return $query->where('orders.order_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->month !== '', function ($query) {
                return $query->whereMonth('orders.created_at', $this->month);
            })
            ->when($this->year !== '', function ($query) {
                return $query->whereYear('orders.created_at', $this->year);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        if ($sales->isEmpty()) {
            if ($this->year !== '' && $this->month !== '') {
                session()->flash(
                    'error',
                    'Data penjualan pada bulan ' .
                        $this->months[$this->month - 1] .
                        ' tahun ' .
                        $this->year .
                        ' tidak ditemukan.',
                );
            } elseif ($this->year !== '') {
                session()->flash('error', 'Data penjualan pada tahun ' . $this->year . ' tidak ditemukan.');
            } elseif ($this->month !== '') {
                session()->flash(
                    'error',
                    'Data penjualan bulan ' . $this->months[$this->month - 1] . ' tidak ditemukan.',
                );
            } else {
                session()->flash('error', 'Data penjualan tidak ditemukan.');
            }

            return redirect()->route('admin.sales.index');
        }

        return $this->documentService->generateSalesReport(
            sales: $sales,
            month: $this->month ? $this->months[$this->month - 1] : '',
            year: $this->year,
        );
    }
}; ?>

<div>
    <div class="flex flex-col items-center justify-end gap-4 pb-4 md:flex-row md:items-start">
        <div class="flex flex-col items-start gap-4 sm:flex-row">
            <div class="flex items-center gap-x-2">
                <x-form.input-label for="sales-month" value="Bulan :" :required="false" class="w-fit shrink-0" />
                <select
                    wire:model.lazy="month"
                    id="sales-month"
                    name="sales-month"
                    class="block w-44 rounded-md border border-neutral-300 px-4 py-3 text-sm text-black focus:border-primary focus:ring-primary"
                >
                    <option value="">Semua bulan</option>
                    @foreach ($months as $month)
                        <option value="{{ $loop->iteration }}">{{ $month }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="flex items-center gap-x-2">
                    <x-form.input-label for="sales-year" value="Tahun :" :required="false" class="w-fit shrink-0" />
                    <select
                        wire:model.lazy="year"
                        id="sales-year"
                        name="sales-year"
                        @class([
                            'block w-44 rounded-md px-4 py-3 text-sm text-black focus:ring-primary',
                            'border border-neutral-300 focus:border-primary' => ! $errors->has('year'),
                            'border border-red-500 ring-red-500 focus:border-red-500' => $errors->has('year'),
                        ])
                    >
                        <option value="">Pilih tahun</option>
                        @for ($year = 2025; $year <= now()->year + 5; $year++)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <x-form.input-error :messages="$errors->get('year')" class="mt-2 max-w-56" />
            </div>
        </div>
        @can('download reports')
            <x-common.button
                variant="primary-light"
                wire:click="download"
                :disabled="empty($this->sales)"
                class="w-full md:w-fit"
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
                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                    <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                    <path d="M12 18v-6" />
                    <path d="m9 15 3 3 3-3" />
                </svg>
                Unduh Laporan
            </x-common.button>
        @endcan

        @can('create offline orders')
            <x-common.button
                :href="route('admin.sales.create')"
                variant="primary"
                class="w-full md:w-fit"
                wire:navigate
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
                Tambah Penjualan
            </x-common.button>
        @endcan
    </div>
    <x-datatable.table searchable="report">
        <x-slot name="head">
            <x-datatable.heading align="center">No.</x-datatable.heading>
            <x-datatable.heading
                sortable
                class="min-w-48"
                :direction="$sortField === 'product_name' ? $sortDirection : null "
                wire:click="sortBy('product_name')"
                align="left"
            >
                Nama Produk
            </x-datatable.heading>
            <x-datatable.heading
                sortable
                class="min-w-48"
                :direction="$sortField === 'variation_name' ? $sortDirection : null "
                wire:click="sortBy('variation_name')"
                align="left"
            >
                Variasi
            </x-datatable.heading>
            <x-datatable.heading
                sortable
                class="min-w-32"
                :direction="$sortField === 'order_detail_quantity' ? $sortDirection : null "
                wire:click="sortBy('order_detail_quantity')"
                align="center"
            >
                Terjual
            </x-datatable.heading>
            <x-datatable.heading
                sortable
                class="min-w-36"
                :direction="$sortField === 'order_detail_price' ? $sortDirection : null "
                wire:click="sortBy('order_detail_price')"
                align="left"
            >
                Harga Satuan
            </x-datatable.heading>
            <x-datatable.heading
                sortable
                class="min-w-36"
                :direction="$sortField === 'product_cost_price' ? $sortDirection : null "
                wire:click="sortBy('product_cost_price')"
                align="left"
            >
                Harga Modal
            </x-datatable.heading>
            <x-datatable.heading class="min-w-36" align="left">Margin Profit</x-datatable.heading>
            <x-datatable.heading class="min-w-40" align="left">Total Penjualan</x-datatable.heading>
            <x-datatable.heading class="min-w-40" align="left">Total Profit</x-datatable.heading>
            <x-datatable.heading class="px-4 py-2"></x-datatable.heading>
        </x-slot>
        <x-slot name="body">
            @forelse ($this->sales as $sale)
                <x-datatable.row
                    valign="middle"
                    wire:loading.class="opacity-50"
                    wire:target="search,sortBy,resetSearch,perPage,month,year"
                >
                    <x-datatable.cell class="text-sm font-normal tracking-tight text-black/70" align="center">
                        {{ $loop->iteration . '.' }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-medium tracking-tight text-black" align="left">
                        {{ $sale->product_name }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="left">
                        @if ($sale->variation_name && $sale->variant_name)
                            {{ ucwords($sale->variation_name) . ' : ' . ucwords($sale->variant_name) }}
                        @else
                            -
                        @endif
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="center">
                        {{ formatPrice($sale->order_detail_quantity) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="left">
                        Rp {{ formatPrice($sale->order_detail_price) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="left">
                        Rp {{ formatPrice($sale->product_cost_price) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="left">
                        Rp {{ formatPrice($sale->order_detail_price - $sale->product_cost_price) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="left">
                        Rp {{ formatPrice($sale->order_detail_price * $sale->order_detail_quantity) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="left">
                        Rp
                        {{ formatPrice(($sale->order_detail_price - $sale->product_cost_price) * $sale->order_detail_quantity) }}
                    </x-datatable.cell>
                    <x-datatable.cell class="relative">
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
                    </x-datatable.cell>
                </x-datatable.row>
            @empty
                <x-datatable.row
                    wire:loading.class="opacity-50"
                    wire:target="search,sortBy,resetSearch,perPage,month,year"
                >
                    <x-datatable.cell class="p-4" colspan="9" align="center">
                        <div class="my-4 flex h-full flex-col items-center justify-center">
                            <div class="mb-6 size-72">
                                {!! file_get_contents(public_path('images/illustrations/empty.svg')) !!}
                            </div>
                            <div class="flex flex-col items-center">
                                <h2 class="mb-3 text-center !text-2xl text-black">Data Penjualan Tidak Ditemukan</h2>
                                <p class="text-center text-base font-normal tracking-tight text-black/70">
                                    @if ($this->search)
                                        Data penjualan yang Anda cari tidak ditemukan, silakan coba untuk mengubah kata kunci
                                        pencarian Anda.
                                    @elseif ($this->month)
                                        Data penjualan pada bulan {{ $this->months[$this->month - 1] }} tidak
                                        ditemukan, silakan coba untuk mengubah bulan penjualan Anda.
                                    @elseif ($this->year)
                                        Data penjualan pada tahun {{ $this->year }} tidak ditemukan, silakan coba untuk
                                        mengubah tahun penjualan Anda.
                                    @else
                                            Seluruh data penjualan Anda akan ditampilkan di halaman ini.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </x-datatable.cell>
                </x-datatable.row>
            @endforelse
        </x-slot>
        <x-slot name="loader">
            <div
                class="absolute left-1/2 top-[50%-1rem] h-full -translate-x-1/2 -translate-y-1/2"
                wire:loading
                wire:target="search,sortBy,resetSearch,perPage,month,year"
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
            {{ $this->sales->links('components.common.pagination') }}
        </x-slot>
    </x-datatable.table>
</div>
