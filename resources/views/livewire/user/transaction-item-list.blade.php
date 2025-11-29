<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\DocumentService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    protected DocumentService $documentService;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public int $perPage = 5;

    public function boot(DocumentService $documentService): void
    {
        $this->documentService = $documentService;
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
    public function payments(): LengthAwarePaginator
    {
        return Payment::queryByUserId(
            userId: auth()->id(),
            columns: [
                'payments.id',
                'payments.order_id',
                'payments.method',
                'payments.status',
                'payments.created_at',
                'payments.updated_at',
                'refunds.status as refund_status',
                'orders.order_number',
                'orders.total_amount',
            ],
        )
            ->whereIn('payments.status', ['paid', 'settled', 'refunded'])
            ->when($this->search, function ($query) {
                return $query->where('orders.order_number', 'like', '%' . $this->search . '%');
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
     * Sort the admins by the specified field.
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    /**
     * Download order invoice.
     *
     * @param   string  $id - The ID of the order invoice to download.
     *
     * @return  redirect if the order is not found.
     * @return  void
     */
    public function downloadInvoice(string $id)
    {
        $order = Order::find($id);

        if (! $order) {
            session()->flash('error', 'Pesanan tidak dapat ditemukan.');
            return $this->redirectIntended(route('orders.index'), navigate: true);
        }

        return $this->documentService->generateInvoice($order);
    }
}; ?>

<div>
    <div class="mb-6 flex flex-col gap-y-3">
        <div class="flex w-full flex-col-reverse justify-between gap-4 md:flex-row md:items-center">
            <div class="relative w-full shrink">
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
                        id="transaction-search"
                        name="transaction-search"
                        wire:model.live.debounce.250ms="search"
                        class="block w-full ps-10"
                        type="text"
                        role="combobox"
                        placeholder="Cari data transaksi berdasarkan nomor pesanan..."
                        autocomplete="off"
                    />
                    <div
                        wire:loading
                        wire:target="search, resetSearch"
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
                            wire:target="search, resetSearch"
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
            <div class="ml-auto inline-flex shrink-0 items-center gap-2">
                <select
                    id="per-page"
                    class="block w-16 rounded-md border border-neutral-300 p-3 text-sm text-black focus:border-primary focus:ring-primary"
                    wire:model.lazy="perPage"
                >
                    @for ($i = 5; $i <= 25; $i += 5)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                <span class="text-sm font-medium tracking-tight text-black">data per halaman</span>
            </div>
        </div>
    </div>
    <div class="rounded-lg border border-neutral-300 shadow-sm">
        <x-datatable.table>
            <x-slot name="head">
                <x-datatable.row>
                    <x-datatable.heading align="center">No.</x-datatable.heading>
                    <x-datatable.heading
                        sortable
                        class="min-w-40"
                        :direction="$sortField === 'id' ? $sortDirection : null "
                        wire:click="sortBy('id')"
                        align="left"
                    >
                        ID Pembayaran
                    </x-datatable.heading>
                    <x-datatable.heading
                        sortable
                        class="min-w-40"
                        :direction="$sortField === 'order_number' ? $sortDirection : null "
                        wire:click="sortBy('order_number')"
                        align="left"
                    >
                        Nomor Pesanan
                    </x-datatable.heading>
                    <x-datatable.heading
                        sortable
                        class="min-w-48"
                        :direction="$sortField === 'method' ? $sortDirection : null "
                        wire:click="sortBy('method')"
                        align="left"
                    >
                        Metode Pembayaran
                    </x-datatable.heading>
                    <x-datatable.heading
                        sortable
                        class="min-w-40"
                        :direction="$sortField === 'total_amount' ? $sortDirection : null "
                        wire:click="sortBy('total_amount')"
                        align="left"
                    >
                        Total Transaksi
                    </x-datatable.heading>
                    <x-datatable.heading
                        sortable
                        class="min-w-40"
                        :direction="$sortField === 'status' ? $sortDirection : null "
                        wire:click="sortBy('status')"
                        align="center"
                    >
                        Status
                    </x-datatable.heading>
                    <x-datatable.heading
                        sortable
                        class="min-w-56"
                        :direction="$sortField === 'created_at' ? $sortDirection : null "
                        wire:click="sortBy('created_at')"
                        align="left"
                    >
                        Dibuat Pada
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
                @forelse ($this->payments as $payment)
                    <x-datatable.row
                        wire:key="{{ $payment->id }}"
                        wire:loading.class="opacity-50"
                        wire:target="search,sortBy,resetSearch,perPage"
                    >
                        <x-datatable.cell
                            class="text-nowrap text-sm font-normal tracking-tight text-black/70"
                            align="center"
                        >
                            {{ $loop->iteration . '.' }}
                        </x-datatable.cell>
                        <x-datatable.cell
                            class="text-nowrap text-sm font-medium tracking-tight text-black"
                            align="left"
                        >
                            {{ $payment->id }}
                        </x-datatable.cell>
                        <x-datatable.cell
                            class="text-nowrap text-sm font-normal tracking-tight text-black/70"
                            align="left"
                        >
                            {{ $payment->order_number }}
                        </x-datatable.cell>
                        <x-datatable.cell class="flex flex-nowrap items-center gap-x-2" align="left">
                            @php
                                $paymentMethod = str_replace(['ewallet_', 'bank_transfer_'], '', $payment->method);
                            @endphp

                            <div class="h-8 w-14 rounded-md border border-neutral-300 px-2 py-1">
                                <img
                                    src="{{ asset('images/logos/payments/' . $paymentMethod . '.webp') }}"
                                    alt="Logo {{ strtoupper($paymentMethod) }}"
                                    title="{{ strtoupper($paymentMethod) }}"
                                    class="h-full w-full object-contain"
                                    loading="lazy"
                                />
                            </div>
                            <p class="text-nowrap text-sm font-normal tracking-tight text-black/70">
                                {{ strtoupper($paymentMethod) }}
                            </p>
                        </x-datatable.cell>
                        <x-datatable.cell
                            class="text-nowrap text-sm font-normal tracking-tight text-black/70"
                            align="left"
                        >
                            Rp {{ formatPrice($payment->total_amount) }}
                        </x-datatable.cell>
                        <x-datatable.cell align="center">
                            @if (in_array($payment->status, ['paid', 'settled']))
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                                    Berhasil
                                </span>
                            @elseif ($payment->status === 'refunded' && $payment->refund_status === 'pending')
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-medium tracking-tight text-blue-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-blue-800"></span>
                                    Mengajukan Refund
                                </span>
                            @elseif ($payment->status === 'refunded' && $payment->refund_status === 'succeeded')
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                                    Berhasil Direfund
                                </span>
                            @elseif ($payment->status === 'refunded' && $payment->refund_status === 'rejected')
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-medium tracking-tight text-red-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-red-800"></span>
                                    Refund Ditolak
                                </span>
                            @elseif ($payment->status === 'refunded' && $payment->refund_status === 'failed')
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-medium tracking-tight text-red-800"
                                >
                                    <span class="inline-block size-1.5 rounded-full bg-red-800"></span>
                                    Refund Gagal
                                </span>
                            @endif
                        </x-datatable.cell>
                        <x-datatable.cell
                            class="text-nowrap text-sm font-normal tracking-tight text-black/70"
                            align="left"
                        >
                            {{ formatTimestamp($payment->created_at) }}
                        </x-datatable.cell>
                        <x-datatable.cell
                            class="text-nowrap text-sm font-normal tracking-tight text-black/70"
                            align="left"
                        >
                            {{ formatTimestamp($payment->updated_at) }}
                        </x-datatable.cell>
                        <x-datatable.cell class="relative" align="right">
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
                                    <x-common.dropdown-link
                                        :href="route('transactions.show', ['id' => $payment->id])"
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
                                    <x-common.dropdown-link
                                        x-on:click.prevent.stop="$wire.downloadInvoice('{{ $payment->order_id }}')"
                                        wire:loading.class="!pointers-event-none !cursor-wait opacity-50"
                                        wire:target="downloadInvoice('{{ $payment->order_id }}')"
                                    >
                                        <svg
                                            class="size-4 shrink-0"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                            <polyline points="7 10 12 15 17 10" />
                                            <line x1="12" x2="12" y1="15" y2="3" />
                                        </svg>
                                        Unduh Invoice
                                    </x-common.dropdown-link>
                                </x-slot>
                            </x-common.dropdown>
                        </x-datatable.cell>
                    </x-datatable.row>
                @empty
                    <tr wire:loading.class="opacity-50" wire:target="search,sortBy,resetSearch,perPage">
                        <td class="p-4" colspan="7" align="center">
                            <figure class="my-4 flex h-full flex-col items-center justify-center">
                                <div class="mb-6 size-72">
                                    {!! file_get_contents(public_path('images/illustrations/empty.svg')) !!}
                                </div>
                                <figcaption class="flex flex-col items-center">
                                    <h2 class="mb-3 text-center !text-2xl text-black">Admin Tidak Ditemukan</h2>
                                    <p class="text-center text-base font-normal tracking-tight text-black/70">
                                        @if ($search)
                                            Data admin dengan nama
                                            <strong>"{{ $search }}"</strong>
                                            tidak ditemukan, silakan coba untuk mengubah kata kunci pencarian Anda.
                                        @else
                                            Seluruh admin Anda akan ditampilkan di halaman ini. Anda dapat menambahkan
                                            admin baru dengan menekan tombol
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
                {{ $this->payments->links('components.common.pagination') }}
            </x-slot>
        </x-datatable.table>
    </div>
</div>
