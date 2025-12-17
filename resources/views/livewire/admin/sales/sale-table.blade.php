<?php

use App\Models\Order;
use App\Services\DocumentService;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
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

    public string $sortField = 'product_name';
    public string $sortDirection = 'asc';
    public int $perPage = 10;

    #[Locked]
    public string $currentTabs = '';

    public function boot(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function mount()
    {
        $this->month = Carbon::now()->month;
        $this->year = date('Y');
        $this->currentTabs = 'products';
    }

    /**
     * Lazy loading that displays the table skeleton with dynamic table rows.
     */
    public function placeholder(): View
    {
        $totalRows = 8;

        return view('components.skeleton.table', compact('totalRows'));
    }

    public function changeTabs(string $tabs)
    {
        if (! in_array($tabs, ['products', 'transactions'])) {
            return;
        }

        if ($tabs === 'products') {
            $this->sortField = 'product_name';
            $this->sortDirection = 'asc';
        } else {
            $this->sortField = 'order_number';
            $this->sortDirection = 'asc';
        }

        $this->currentTabs = $tabs;
    }

    #[Computed]
    public function sales()
    {
        $columns = ['orders.order_number'];

        $relations = ['order_details'];

        if ($this->currentTabs === 'transactions') {
            $columns[] = 'orders.source';

            $relations[] = 'payment';
        }

        $sales = Order::queryAllByStatusWithRelations(status: 'completed', columns: $columns, relations: $relations)
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

        if ($this->currentTabs === 'products') {
            $grouped = $sales
                ->getCollection()
                ->groupBy('product_variant_id')
                ->map(function ($sale) {
                    $first = $sale->first();

                    $first->order_detail_quantity = $sale->sum('order_detail_quantity');

                    return $first;
                });

            $sales->setCollection($grouped);
        } elseif ($this->currentTabs === 'transactions') {
            $grouped = $sales
                ->getCollection()
                ->groupBy('order_number')
                ->map(function ($sale) {
                    $orderDetails = $sale
                        ->map(function ($item) {
                            return (object) [
                                'order_detail_id' => $item->order_detail_id,
                                'order_detail_price' => $item->order_detail_price,
                                'order_detail_quantity' => $item->order_detail_quantity,
                                'product_variant_id' => $item->product_variant_id,
                                'product_variant_sku' => $item->product_variant_sku,
                                'product_name' => $item->product_name,
                                'product_slug' => $item->product_slug,
                                'product_main_sku' => $item->product_main_sku,
                                'product_cost_price' => $item->product_cost_price,
                                'variant_name' => $item->variant_name,
                                'variation_name' => $item->variation_name,
                                'thumbnail' => $item->thumbnail,
                                'subcategory_name' => $item->subcategory_name,
                                'category_name' => $item->category_name,
                            ];
                        })
                        ->values()
                        ->all();

                    return (object) [
                        'order_number' => $sale->first()->order_number,
                        'source' => $sale->first()->source,
                        'order_details' => $orderDetails,
                        'payment_method' => $sale->first()->payment_method ? $sale->first()->payment_method : 'offline',
                    ];
                })
                ->values();

            $sales->setCollection($grouped);
        }

        return $sales;
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

        $validated = $this->validate(
            rules: [
                'currentTabs' => 'required|string|in:products,transactions',
                'month' => 'nullable|integer|between:1,12',
                'year' => 'required|digits:4',
            ],
            messages: [
                'currentTabs.required' => 'Pilih opsi yang tersedia',
                'currentTabs.string' => 'Pilih opsi yang tersedia',
                'currentTabs.in' => 'Opsi yang dapat dipilih antara "Per-Produk" atau "Per-Transaksi"',
                'month.integer' => 'Pilih bulan yang tersedia.',
                'month.between' => 'Pilih bulan yang tersedia.',
                'year.required' => 'Tahun wajib dipilih.',
                'year.digits' => 'Tahun harus terdiri dari 4 digit.',
            ],
            attributes: [
                'currentTabs' => 'Opsi',
                'month' => 'Bulan',
                'year' => 'Tahun',
            ],
        );

        $columns = ['orders.order_number'];

        $relations = ['order_details'];

        if ($validated['currentTabs'] === 'transactions') {
            $columns[] = 'orders.source';

            $relations[] = 'payment';
        }

        $sales = Order::queryAllByStatusWithRelations(status: 'completed', columns: $columns, relations: $relations)
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

        if ($this->currentTabs === 'products') {
            return $this->documentService->generateSalesReport(
                sales: $sales,
                month: $this->month ? $this->months[$this->month - 1] : '',
                year: $this->year,
                options: 'products',
            );
        } elseif ($this->currentTabs === 'transactions') {
            $sales = $sales
                ->groupBy('order_number')
                ->map(function ($sale) {
                    $orderDetails = $sale
                        ->map(function ($item) {
                            return [
                                'order_detail_id' => $item->order_detail_id,
                                'order_detail_price' => $item->order_detail_price,
                                'order_detail_quantity' => $item->order_detail_quantity,
                                'product_variant_id' => $item->product_variant_id,
                                'product_variant_sku' => $item->product_variant_sku,
                                'product_name' => $item->product_name,
                                'product_slug' => $item->product_slug,
                                'product_main_sku' => $item->product_main_sku,
                                'product_cost_price' => $item->product_cost_price,
                                'variant_name' => $item->variant_name,
                                'variation_name' => $item->variation_name,
                                'thumbnail' => $item->thumbnail,
                                'subcategory_name' => $item->subcategory_name,
                                'category_name' => $item->category_name,
                            ];
                        })
                        ->values()
                        ->all();

                    return [
                        'order_number' => $sale->first()->order_number,
                        'source' => $sale->first()->source,
                        'order_details' => $orderDetails,
                        'payment_method' => $sale->first()->payment_method ? $sale->first()->payment_method : 'offline',
                    ];
                })
                ->values();

            return $this->documentService->generateSalesReport(
                sales: $sales,
                month: $this->month ? $this->months[$this->month - 1] : '',
                year: $this->year,
                options: 'transactions',
            );
        }
    }
}; ?>

<div>
    <div class="pb-4">
        <ul class="flex gap-4">
            <li class="w-full focus-within:z-10">
                <button
                    wire:click="changeTabs('products')"
                    @class([
                        'inline-block w-full rounded-lg p-4 text-sm font-semibold tracking-tight transition-colors',
                        'border border-primary bg-primary-100 text-primary' => $currentTabs === 'products',
                        'border border-neutral-300 bg-white text-black hover:bg-neutral-100 focus:outline-none focus:ring-4 focus:ring-primary-600' =>
                            $currentTabs !== 'products',
                    ])
                >
                    Per-Produk
                </button>
            </li>
            <li class="w-full focus-within:z-10">
                <button
                    wire:click="changeTabs('transactions')"
                    @class([
                        'inline-block w-full rounded-lg p-4 text-sm font-semibold tracking-tight transition-colors',
                        'border border-primary bg-primary-100 text-primary' => $currentTabs === 'transactions',
                        'border border-neutral-300 bg-white text-black hover:bg-neutral-100 focus:outline-none focus:ring-4 focus:ring-primary-600' =>
                            $currentTabs !== 'transactions',
                    ])
                >
                    Per-Transaksi
                </button>
            </li>
        </ul>
        <x-form.input-error :messages="$errors->get('currentTabs')" class="mt-2 max-w-44" />
    </div>
    <div class="flex flex-col items-center justify-end gap-4 pb-4 md:flex-row md:items-start">
        <div class="flex flex-col items-start gap-4 sm:flex-row">
            <div>
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
                <x-form.input-error :messages="$errors->get('month')" class="mt-2 max-w-44" />
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
                <x-form.input-error :messages="$errors->get('year')" class="mt-2 max-w-44" />
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
            @if ($currentTabs === 'products')
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
            @elseif ($currentTabs === 'transactions')
                <x-datatable.heading
                    sortable
                    class="min-w-48"
                    :direction="$sortField === 'order_number' ? $sortDirection : null "
                    wire:click="sortBy('order_number')"
                    align="left"
                >
                    Nomor Pesanan
                </x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-36"
                    :direction="$sortField === 'source' ? $sortDirection : null "
                    wire:click="sortBy('source')"
                    align="center"
                >
                    Sumber Penjualan
                </x-datatable.heading>
                <x-datatable.heading class="min-w-48" align="left">Produk yang dibeli</x-datatable.heading>
                <x-datatable.heading
                    sortable
                    class="min-w-40"
                    :direction="$sortField === 'payment_method' ? $sortDirection : null"
                    wire:click="sortBy('payment_method')"
                    align="center"
                >
                    Metode Pembayaran
                </x-datatable.heading>
                <x-datatable.heading class="min-w-36" align="center">Total Penjualan</x-datatable.heading>
                <x-datatable.heading class="min-w-36" align="center">Total Profit</x-datatable.heading>
            @endif
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
                    @if ($currentTabs === 'products')
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
                                        :href="route('admin.products.show', ['slug' => $sale->product_slug])"
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
                                        Detail Produk
                                    </x-common.dropdown-link>
                                </x-slot>
                            </x-common.dropdown>
                        </x-datatable.cell>
                    @elseif ($currentTabs === 'transactions')
                        <x-datatable.cell class="text-sm font-medium tracking-tight text-black" align="left">
                            {{ $sale->order_number }}
                        </x-datatable.cell>
                        <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="center">
                            {{ $sale->source === 'ecommerce' ? 'Website Online' : 'Penjualan Offline' }}
                        </x-datatable.cell>
                        <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="center">
                            <ul class="ms-4 flex list-disc flex-col items-start gap-y-2">
                                @foreach ($sale->order_details as $item)
                                    <li>{{ $item->order_detail_quantity . 'x ' . $item->product_name }}</li>
                                @endforeach
                            </ul>
                        </x-datatable.cell>
                        @if ($sale->payment_method === 'offline')
                            <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="center">
                                Penjualan {{ ucwords($sale->payment_method) }}
                            </x-datatable.cell>
                        @else
                            <x-datatable.cell
                                class="flex items-center justify-center gap-x-2 text-sm font-medium tracking-tight text-black/70"
                                align="center"
                            >
                                @php
                                    $paymentMethod = null;

                                    if (str_contains($sale->payment_method, 'bank_transfer_')) {
                                        $paymentMethod = str_replace('bank_transfer_', '', $sale->payment_method);
                                    } elseif (str_contains($sale->payment_method, 'ewallet_')) {
                                        $paymentMethod = str_replace('ewallet_', '', $sale->payment_method);
                                    }
                                @endphp

                                <div class="h-8 w-14">
                                    <img
                                        src="{{ asset('images/logos/payments/' . $paymentMethod . '.webp') }}"
                                        alt="Logo {{ strtoupper($paymentMethod) }}"
                                        title="{{ strtoupper($paymentMethod) }}"
                                        class="h-full w-full object-contain"
                                        loading="lazy"
                                    />
                                </div>
                                {{ strtoupper($paymentMethod) }}
                                {{ str_contains($sale->payment_method, 'bank_transfer_') ? ' VA' : '' }}
                            </x-datatable.cell>
                        @endif
                        <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="center">
                            @php
                                $totalCost = 0;

                                foreach ($sale->order_details as $detail) {
                                    $orderDetailPrice = (float) $detail->order_detail_price;
                                    $orderDetailQuantity = (int) $detail->order_detail_quantity;

                                    $totalCost += $orderDetailPrice * $orderDetailQuantity;
                                }

                                echo 'Rp' . formatPrice($totalCost);
                            @endphp
                        </x-datatable.cell>
                        <x-datatable.cell class="text-sm font-medium tracking-tight text-black/70" align="center">
                            @php
                                $totalProfit = 0;

                                foreach ($sale->order_details as $detail) {
                                    $revenue = (float) $detail->order_detail_price * (int) $detail->order_detail_quantity;
                                    $cost = (float) $detail->product_cost_price * (int) $detail->order_detail_quantity;

                                    $totalProfit += $revenue - $cost;
                                }

                                echo 'Rp' . formatPrice($totalProfit);
                            @endphp
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
                    @endif
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
                                <h2 class="mb-3 text-center !text-2xl text-black">
                                    Data Penjualan {{ $currentTabs === 'products' ? 'Per-Produk' : 'Per-Transaksi' }}
                                    Tidak Ditemukan
                                </h2>
                                <p class="text-center text-base font-normal tracking-tight text-black/70">
                                    @if ($this->search)
                                        Data penjualan
                                        {{ $currentTabs === 'products' ? 'Per-Produk' : 'Per-Transaksi' }} yang Anda
                                        cari tidak ditemukan, silakan coba untuk mengubah kata kunci pencarian Anda.
                                    @elseif ($this->month)
                                        Data penjualan
                                        {{ $currentTabs === 'products' ? 'Per-Produk' : 'Per-Transaksi' }} pada bulan
                                        {{ $this->months[$this->month - 1] }} tidak ditemukan, silakan coba untuk
                                        mengubah bulan penjualan Anda.
                                    @elseif ($this->year)
                                        Data penjualan
                                        {{ $currentTabs === 'products' ? 'Per-Produk' : 'Per-Transaksi' }} pada tahun
                                        {{ $this->year }} tidak ditemukan, silakan coba untuk mengubah tahun penjualan
                                        Anda.
                                    @else
                                        Seluruh data penjualan
                                        {{ $currentTabs === 'products' ? 'Per-Produk' : 'Per-Transaksi' }} Anda akan
                                        ditampilkan di halaman ini.
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
