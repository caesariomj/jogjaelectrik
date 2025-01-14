<?php

use App\Models\Order;
use App\Services\DocumentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    protected DocumentService $documentService;

    public Order $order;

    #[Locked]
    public array $orderStatuses = [
        [
            'code' => 'all',
            'label' => 'Semua',
            'count' => 0,
        ],
        [
            'code' => 'waiting_payment',
            'label' => 'Menunggu Pembayaran',
            'count' => 0,
        ],
        [
            'code' => 'payment_received',
            'label' => 'Untuk Diproses',
            'count' => 0,
        ],
        [
            'code' => 'processing',
            'label' => 'Untuk Dikirim',
            'count' => 0,
        ],
        [
            'code' => 'shipping',
            'label' => 'Dalam Pengiriman',
            'count' => 0,
        ],
        [
            'code' => 'completed',
            'label' => 'Berhasil',
            'count' => 0,
        ],
        [
            'code' => 'failed',
            'label' => 'Gagal',
            'count' => 0,
        ],
        [
            'code' => 'canceled',
            'label' => 'Dibatalkan',
            'count' => 0,
        ],
    ];
    public string $status = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public string $shipmentTrackingNumber = '';
    public string $cancelationReason = '';
    public string $otherCancelationReason = '';

    public function boot(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function mount()
    {
        $this->status = 'all';

        foreach ($this->orderStatuses as &$status) {
            if ($status['code'] == 'all') {
                $status['count'] = Order::count();
            } else {
                $status['count'] = Order::where('status', $status['code'])->count();
            }
        }
    }

    #[Computed]
    public function orders()
    {
        $search = $this->search;
        $status = $this->status;

        return Order::with(['details.productVariant.product.images', 'payment', 'user.city.province'])
            ->when($search !== '', function ($query) use ($search) {
                return $query->where('order_number', 'like', '%' . $search . '%');
            })
            ->when($status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function resetSearch()
    {
        $this->reset('search');
    }

    public function downloadInvoice(string $id)
    {
        $order = Order::find($id);

        if (! $order) {
            session()->flash('error', 'Pesanan tidak dapat ditemukan.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }

        return $this->documentService->generateInvoice($order);
    }

    public function downloadShippingLabel(string $id)
    {
        $order = Order::find($id);

        if (! $order) {
            session()->flash('error', 'Pesanan tidak dapat ditemukan.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }

        return $this->documentService->generateShippingLabel($order);
    }

    public function processOrder(string $id)
    {
        $order = Order::find($id);

        if (! $order) {
            session()->flash('error', 'Pesanan dengan nomor: ' . $order->order_number . ' tidak dapat ditemukan.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }

        try {
            $this->authorize('update', $order);

            DB::transaction(function () use ($order) {
                $order->update([
                    'status' => 'processing',
                ]);
            });

            session()->flash('success', 'Pesanan dengan nomor: ' . $order->order_number . ' berhasil diproses.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected order processing error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }
    }

    #[On('initiate-order-shipping')]
    public function confirmShipOrder(string $id)
    {
        $this->order = Order::find($id);

        if (! $this->order) {
            session()->flash('error', 'Pesanan tidak dapat ditemukan.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }

        $this->dispatch('open-modal', 'confirm-order-shipping-' . $this->order->id);
    }

    public function shipOrder()
    {
        $validated = $this->validate(
            rules: [
                'shipmentTrackingNumber' => 'required|string|min:5|max:50',
            ],
            attributes: [
                'shipmentTrackingNumber' => 'Nomor resi pengiriman',
            ],
        );

        $order = $this->order;

        try {
            $this->authorize('update', $order);

            DB::transaction(function () use ($order, $validated) {
                $order->update([
                    'status' => 'shipping',
                    'shipment_tracking_number' => strtoupper($validated['shipmentTrackingNumber']),
                ]);
            });

            session()->flash('success', 'Pesanan dengan nomor: ' . $order->order_number . ' berhasil dikirim.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected order shipping error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }
    }

    #[On('initiate-order-cancellation')]
    public function confirmCancelOrder(string $id)
    {
        $this->order = Order::find($id);

        if (! $this->order) {
            session()->flash('error', 'Pesanan tidak dapat ditemukan.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }

        $this->dispatch('open-modal', 'confirm-order-cancellation-' . $this->order->id);
    }

    public function cancelOrder()
    {
        $validated = $this->validate(
            rules: [
                'cancelationReason' => 'required|string|max:255',
                'otherCancelationReason' => 'nullable|required_if:cancelationReason,alasan_lainnya|string|max:255',
            ],
            attributes: [
                'cancelationReason' => 'Alasan pembatalan',
                'otherCancelationReason' => 'Alasan pembatalan lainnya',
            ],
        );

        $order = $this->order;

        try {
            $this->authorize('cancel', $order);

            DB::transaction(function () use ($order, $validated) {
                if ($order->status === 'waiting_for_payment') {
                    $order->payment->update([
                        'status' => 'cancel',
                    ]);
                } else {
                    $order->payment->update([
                        'status' => 'refund',
                    ]);
                }

                $cancelationReason = 'Dibatalkan oleh admin: ';
                if ($validated['otherCancelationReason'] !== '') {
                    $cancelationReason .= strtolower($validated['otherCancelationReason']);
                } else {
                    $cancelationReason .= strtolower(str_replace('_', ' ', $validated['cancelationReason']));
                }

                $order->update([
                    'status' => 'canceled',
                    'cancelation_reason' => $cancelationReason,
                ]);
            });

            session()->flash('success', 'Pesanan dengan nomor: ' . $order->order_number . ' berhasil dibatalkan.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected order cancelation error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }
    }

    #[On('initiate-order-finishing')]
    public function confirmFinishOrder(string $id)
    {
        $this->order = Order::find($id);

        if (! $this->order) {
            session()->flash('error', 'Pesanan tidak dapat ditemukan.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }

        $this->dispatch('open-modal', 'confirm-order-finishing-' . $this->order->id);
    }

    public function finishOrder()
    {
        $order = $this->order;

        try {
            $this->authorize('update', $order);

            DB::transaction(function () use ($order) {
                $order->update([
                    'status' => 'completed',
                ]);
            });

            session()->flash('success', 'Pesanan dengan nomor: ' . $order->order_number . ' berhasil diselesaikan.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected order finishing error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.orders.index'), navigate: true);
        }
    }
}; ?>

<div>
    <div class="mb-6 flex flex-col gap-y-3">
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
                    id="order-search"
                    name="order-search"
                    wire:model.live.debounce.250ms="search"
                    class="block w-full ps-10"
                    type="text"
                    role="combobox"
                    placeholder="Cari data pesanan berdasarkan nomor pesanan..."
                    autocomplete="off"
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
        <ul class="flex flex-nowrap gap-x-2 overflow-x-auto pb-2 text-center">
            @foreach ($orderStatuses as $orderStatus)
                <li>
                    <x-form.radio
                        :inputAttributes="
                            [
                                'wire:model.lazy' => 'status',
                                'id' => 'status-' . $orderStatus['code'],
                                'code' => 'select-order-status',
                                'value' => $orderStatus['code'],
                            ]
                        "
                        :labelAttributes="
                            [
                                'for' => 'status-' . $orderStatus['code'],
                            ]
                        "
                    >
                        <svg
                            class="size-5 shrink-0"
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
                            @if ($orderStatus['code'] === 'all')
                                <rect width="8" height="4" x="8" y="2" rx="1" ry="1" />
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                                <path d="M12 11h4" />
                                <path d="M12 16h4" />
                                <path d="M8 11h.01" />
                                <path d="M8 16h.01" />
                            @elseif ($orderStatus['code'] === 'waiting_payment')
                                <rect width="20" height="12" x="2" y="6" rx="2" />
                                <circle cx="12" cy="12" r="2" />
                                <path d="M6 12h.01M18 12h.01" />
                            @elseif ($orderStatus['code'] === 'payment_received')
                                <path
                                    d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"
                                />
                                <path d="m7.5 4.27 9 5.15" />
                                <polyline points="3.29 7 12 12 20.71 7" />
                                <line x1="12" x2="12" y1="22" y2="12" />
                                <circle cx="18.5" cy="15.5" r="2.5" />
                                <path d="M20.27 17.27 22 19" />
                            @elseif ($orderStatus['code'] === 'processing')
                                <path d="m16 16 2 2 4-4" />
                                <path
                                    d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"
                                />
                                <path d="m7.5 4.27 9 5.15" />
                                <polyline points="3.29 7 12 12 20.71 7" />
                                <line x1="12" x2="12" y1="22" y2="12" />
                            @elseif ($orderStatus['code'] === 'shipping')
                                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" />
                                <path d="M15 18H9" />
                                <path
                                    d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"
                                />
                                <circle cx="17" cy="18" r="2" />
                                <circle cx="7" cy="18" r="2" />
                            @elseif ($orderStatus['code'] === 'completed')
                                <circle cx="12" cy="12" r="10" />
                                <path d="m9 12 2 2 4-4" />
                            @elseif (in_array($orderStatus['code'], ['failed', 'canceled']))
                                <circle cx="12" cy="12" r="10" />
                                <path d="m15 9-6 6" />
                                <path d="m9 9 6 6" />
                            @endif
                        </svg>
                        <span class="whitespace-nowrap text-sm font-medium">
                            {{ $orderStatus['label'] }}
                        </span>

                        @if ($orderStatus['count'] > 0)
                            <span class="whitespace-nowrap text-sm font-medium">
                                {{ $orderStatus['count'] > 100 ? '99+' : $orderStatus['count'] }}
                            </span>
                        @endif
                    </x-form.radio>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="relative space-y-4">
        @forelse ($this->orders as $order)
            <article
                class="relative rounded-lg border border-neutral-300 shadow-sm"
                wire:loading.class="opacity-50"
                wire:target="status, search, resetSearch"
            >
                <header class="mb-4 border-b border-neutral-300 p-4">
                    <div class="flex flex-col gap-2">
                        <h2
                            class="inline-flex flex-col items-start gap-1.5 text-sm font-normal tracking-tight text-black/70 md:flex-row md:items-center"
                        >
                            Nomor Pesanan:
                            <span class="text-xl font-semibold tracking-tight text-black">
                                {{ $order->order_number }}
                            </span>
                        </h2>
                        <div class="inline-flex flex-col items-start gap-y-1.5 md:flex-row md:items-center">
                            <div class="inline-flex items-start md:items-center md:pe-2">
                                <svg
                                    class="me-2 size-5 shrink-0 text-black"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"
                                    />
                                </svg>
                                <p class="text-sm font-medium tracking-tight text-black">
                                    Dibuat pada: {{ formatTimestamp($order->created_at) }}
                                </p>
                            </div>
                            @if (in_array($order->status, ['payment_received', 'processing']))
                                <div
                                    class="inline-flex items-start md:items-center md:border-l md:border-l-neutral-300 md:ps-2"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.8"
                                        stroke="currentColor"
                                        class="me-2 size-5 shrink-0 text-red-800"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                        />
                                    </svg>
                                    <p class="text-sm font-semibold tracking-tight text-red-800">
                                        @if ($order->estimated_shipping_min_days === 0 && $order->estimated_shipping_max_days === 0)
                                            <time datetime="{{ $order->created_at }}">Harus Dikirim Hari Ini</time>
                                        @else
                                            Kirim pesanan sebelum:
                                            <time datetime="{{ $order->created_at->addDays(1) }}">
                                                {{ formatTimestamp($order->created_at->addDays(1)) }}
                                            </time>
                                        @endif
                                    </p>
                                </div>
                            @elseif ($order->status === 'shipping')
                                <div
                                    class="inline-flex items-start md:items-center md:border-l md:border-l-neutral-300 md:ps-2"
                                >
                                    <svg
                                        class="me-2 size-5 shrink-0 text-teal-800"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" />
                                        <path d="M15 18H9" />
                                        <path
                                            d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"
                                        />
                                        <circle cx="17" cy="18" r="2" />
                                        <circle cx="7" cy="18" r="2" />
                                    </svg>
                                    <p class="text-sm font-semibold tracking-tight text-teal-800">
                                        Estimasi tiba:

                                        @if ($order->estimated_shipping_min_days === 0 && $order->estimated_shipping_max_days === 0)
                                            <time datetime="{{ $order->created_at }}">Hari Ini</time>
                                        @elseif ($order->estimated_shipping_min_days === $order->estimated_shipping_max_days)
                                            <time
                                                datetime="{{ $order->created_at->addDays($order->estimated_shipping_min_days) }}"
                                            >
                                                {{ formatDate($order->created_at->addDays($order->estimated_shipping_min_days)) }}
                                            </time>
                                        @else
                                            <time
                                                datetime="{{ $order->created_at->addDays($order->estimated_shipping_min_days) }}"
                                            >
                                                {{ formatDate($order->created_at->addDays($order->estimated_shipping_min_days)) }}
                                            </time>
                                            &mdash;
                                            <time
                                                datetime="{{ $order->created_at->addDays($order->estimated_shipping_max_days) }}"
                                            >
                                                {{ formatDate($order->created_at->addDays($order->estimated_shipping_max_days)) }}
                                            </time>
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="absolute end-4 top-4 inline-flex items-center gap-x-2">
                        <span
                            @class([
                                'inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium tracking-tight',
                                'bg-yellow-100 text-yellow-800' => $order->status === 'waiting_payment',
                                'bg-blue-100 text-blue-800' => $order->status === 'payment_received',
                                'bg-teal-100 text-teal-800' => in_array($order->status, ['processing', 'shipping', 'completed']),
                                'bg-red-100 text-red-800' => in_array($order->status, ['failed', 'canceled']),
                            ])
                            role="status"
                        >
                            <span class="mb-0.5">•</span>
                            @if ($order->status === 'all')
                                Semua
                            @elseif ($order->status === 'waiting_payment')
                                Menunggu Pembayaran
                            @elseif ($order->status === 'payment_received')
                                Untuk Diproses
                            @elseif ($order->status === 'processing')
                                Untuk Dikirim
                            @elseif ($order->status === 'shipping')
                                Dalam Pengiriman
                            @elseif ($order->status === 'completed')
                                Selesai
                            @elseif ($order->status === 'failed')
                                Gagal
                            @elseif ($order->status === 'canceled')
                                Dibatalkan
                            @endif
                        </span>
                        <x-common.dropdown width="56">
                            <x-slot name="trigger">
                                <button type="button" class="rounded-full p-2 text-black hover:bg-neutral-200">
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
                                    x-on:click.prevent.stop="$wire.downloadInvoice('{{ $order->id }}')"
                                    wire:loading.class="!pointers-event-none !cursor-wait opacity-50"
                                    wire:target="downloadInvoice('{{ $order->id }}')"
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
                                <x-common.dropdown-link
                                    x-on:click.prevent.stop="$wire.downloadShippingLabel('{{ $order->id }}')"
                                    wire:loading.class="!pointers-event-none !cursor-wait opacity-50"
                                    wire:target="downloadShippingLabel('{{ $order->id }}')"
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
                                    Unduh Label Pengiriman
                                </x-common.dropdown-link>
                            </x-slot>
                        </x-common.dropdown>
                    </div>
                </header>
                <ul class="mb-8 space-y-4 p-4">
                    @foreach ($order->details as $item)
                        <li wire:key="{{ $item->id }}" class="flex items-start gap-x-4">
                            <a
                                href="{{ route('admin.products.show', ['slug' => $item->productVariant->product->slug]) }}"
                                class="size-20 shrink-0 overflow-hidden rounded-lg bg-neutral-100"
                                wire:navigate
                            >
                                <img
                                    src="{{ asset('storage/uploads/product-images/' .$item->productVariant->product->images()->thumbnail()->first()->file_name,) }}"
                                    alt="Gambar produk {{ strtolower($item->productVariant->product->name) }}"
                                    class="aspect-square h-full w-20 scale-100 object-cover brightness-100 transition-all ease-in-out hover:scale-105 hover:brightness-95"
                                    loading="lazy"
                                />
                            </a>
                            <div class="flex h-20 w-full flex-col items-start">
                                <a
                                    href="{{ route('admin.products.show', ['slug' => $item->productVariant->product->slug]) }}"
                                    class="mb-0.5"
                                    wire:navigate
                                >
                                    <h3 class="!text-base text-black hover:text-primary">
                                        {{ $item->productVariant->product->name }}
                                    </h3>
                                </a>

                                @if ($item->productVariant->variant_sku)
                                    <p class="mb-2 text-sm tracking-tight text-black">
                                        {{ ucwords($item->productVariant->combinations->first()->variationVariant->variation->name) . ': ' . ucwords($item->productVariant->combinations->first()->variationVariant->name) }}
                                    </p>
                                @endif

                                <p
                                    class="inline-flex items-center text-sm font-medium tracking-tighter text-black/70 sm:text-base"
                                >
                                    <span class="me-2">{{ $item->quantity }}</span>
                                    x
                                    <span class="ms-2 tracking-tight text-black">
                                        Rp {{ formatPrice($item->price) }}
                                    </span>
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <footer class="flex flex-col items-start justify-between gap-6 border-t border-t-neutral-300 p-4">
                    <dl class="grid w-full grid-cols-2 place-items-baseline gap-2 md:grid-cols-4">
                        <dt class="inline-flex items-center text-sm font-medium tracking-tight text-black/70">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            Nama Pembeli :
                        </dt>
                        <dd class="text-sm font-medium tracking-tight text-black">{{ $order->user->name }}</dd>
                        <dt class="inline-flex items-center text-sm font-medium tracking-tight text-black/70">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l1.105 4.423a1.875 1.875 0 0 1-.694 1.955l-1.293.97c-.135.101-.164.249-.126.352a11.285 11.285 0 0 0 6.697 6.697c.103.038.25.009.352-.126l.97-1.293a1.875 1.875 0 0 1 1.955-.694l4.423 1.105c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            Nomor Telefon :
                        </dt>
                        <dd class="text-sm font-medium tracking-tight text-black">
                            {{ '0' . \Illuminate\Support\Facades\Crypt::decryptString($order->user->phone_number) }}
                        </dd>

                        @php
                            [$courier, $service] = explode('-', $order->shipping_courier);
                        @endphp

                        <dt class="inline-flex items-center text-sm font-medium tracking-tight text-black/70">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25ZM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 1 1 6 0h3a.75.75 0 0 0 .75-.75V15Z"
                                />
                                <path
                                    d="M8.25 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0ZM15.75 6.75a.75.75 0 0 0-.75.75v11.25c0 .087.015.17.042.248a3 3 0 0 1 5.958.464c.853-.175 1.522-.935 1.464-1.883a18.659 18.659 0 0 0-3.732-10.104 1.837 1.837 0 0 0-1.47-.725H15.75Z"
                                />
                                <path d="M19.5 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" />
                            </svg>
                            Ekspedisi & Layanan Kurir :
                        </dt>
                        <dd class="inline-flex items-center text-sm font-medium tracking-tight text-black">
                            <img
                                src="{{ asset('images/logos/shipping/' . $courier . '.webp') }}"
                                alt="Logo {{ strtoupper($courier) }}"
                                class="me-2 h-auto w-10"
                                loading="lazy"
                            />
                            {{ strtoupper($courier) . ' - ' . strtoupper($service) }}
                            @if ($order->estimated_shipping_min_days === 0 && $order->estimated_shipping_max_days === 0)
                                <span
                                    class="ms-2 inline-flex items-center rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-medium tracking-tight text-primary-800"
                                >
                                    Sameday
                                </span>
                            @endif
                        </dd>
                        <dt class="inline-flex items-center text-sm font-medium tracking-tight text-black/70">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0 0 16.5 9h-1.875a1.875 1.875 0 0 1-1.875-1.875V5.25A3.75 3.75 0 0 0 9 1.5H5.625ZM7.5 15a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 15Zm.75 2.25a.75.75 0 0 0 0 1.5H12a.75.75 0 0 0 0-1.5H8.25Z"
                                    clip-rule="evenodd"
                                />
                                <path
                                    d="M12.971 1.816A5.23 5.23 0 0 1 14.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 0 1 3.434 1.279 9.768 9.768 0 0 0-6.963-6.963Z"
                                />
                            </svg>
                            Catatan Pesanan :
                        </dt>
                        <dd
                            @class([
                                'text-sm font-medium tracking-tight text-black',
                                'not-italic' => ! $order->note,
                                'italic' => $order->note,
                            ])
                        >
                            {{ $order->note ?? 'Tidak ada catatan' }}
                        </dd>
                        <dt class="inline-flex items-center text-sm font-medium tracking-tight text-black/70">
                            <svg
                                class="me-1 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"
                                />
                                <path
                                    d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"
                                />
                            </svg>
                            Alamat Pengiriman :
                        </dt>
                        <dd class="text-sm font-medium tracking-tight text-black">
                            {{ \Illuminate\Support\Facades\Crypt::decryptString($order->shipping_address) . ', ' . \Illuminate\Support\Facades\Crypt::decryptString($order->user->postal_code) . ' - ' . $order->user->city->province->name . ', ' . $order->user->city->name }}
                        </dd>
                    </dl>
                    <div class="flex w-full flex-col items-center justify-between gap-4 md:flex-row">
                        <div class="inline-flex items-center">
                            <p class="text-base font-medium tracking-tight text-black/70">
                                Total Pembayaran :
                                <span class="mx-2 text-lg font-semibold tracking-tight text-black">
                                    Rp {{ formatPrice($order->total_amount) }}
                                </span>
                                <x-common.tooltip
                                    id="total-price-information"
                                    text="Total harga sudah termasuk ongkos kirim dan diskon yang digunakan saat pesanan dibuat."
                                    class="z-[60] w-72"
                                />
                            </p>
                        </div>
                        <div class="inline-flex w-full flex-col items-center gap-2 md:w-fit md:flex-row">
                            <x-common.button
                                :href="route('admin.orders.show', ['orderNumber' => $order->order_number])"
                                variant="secondary"
                                class="w-full md:w-fit"
                                aria-label="Lihat detail pesanan"
                                wire:navigate
                            >
                                Detail Pesanan
                            </x-common.button>

                            @can('cancel', $order)
                                @if (in_array($order->status, ['waiting_payment', 'payment_received', 'processing', 'shipping']))
                                    <x-common.button
                                        variant="danger"
                                        class="w-full md:w-fit"
                                        aria-label="Batalkan pesanan"
                                        x-on:click.prevent.stop="$dispatch('initiate-order-cancellation', { 'id': '{{ $order->id }}' })"
                                        wire:loading.attr="disabled"
                                        wire:target.except="cancelationReason,shipmentTrackingNumber"
                                    >
                                        Batalkan Pesanan
                                    </x-common.button>
                                    <x-common.modal
                                        name="confirm-order-cancellation-{{ $order->id }}"
                                        :show="$errors->isNotEmpty()"
                                        focusable
                                    >
                                        <form wire:submit="cancelOrder" class="flex flex-col items-center p-6">
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
                                            <h2 class="mb-2 text-center text-black">Batalkan Pesanan</h2>
                                            <p
                                                class="mb-4 text-center text-base font-medium tracking-tight text-black/70"
                                            >
                                                Apakah Anda yakin ingin membatalkan pesanan dengan nomor pesanan:
                                                <strong class="text-black">{{ $order->order_number }}</strong>
                                                ? Proses ini
                                                <strong class="text-black">tidak dapat dibatalkan</strong>
                                                , dan perubahan status pesanan akan menjadi
                                                <strong class="text-black">Dibatalkan</strong>
                                                .
                                            </p>
                                            <p class="mb-4 text-center text-sm font-medium tracking-tight text-red-800">
                                                <strong>Catatan:</strong>
                                                Jika pelanggan telah melakukan pembayaran, Anda perlu memproses refund
                                                melalui menu
                                                <a
                                                    href="#"
                                                    class="underline transition-colors hover:text-primary"
                                                    wire:navigate
                                                >
                                                    permintaan refund
                                                </a>
                                                .
                                            </p>
                                            <div class="flex w-full flex-col items-start">
                                                <x-form.input-label value="Alasan Pembatalan" for="reason" />
                                                <select
                                                    wire:model.lazy="cancelationReason"
                                                    name="cancelation-reason"
                                                    id="cancelation-reason"
                                                    class="mt-1 block w-full rounded-lg border-neutral-300 px-4 py-3 pe-9 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                                    required
                                                >
                                                    <option value="" selected>Pilih Alasan Pembatalan Pesanan</option>
                                                    <optgroup label="Masalah Stok">
                                                        <option value="stok_habis">Produk habis (stok kosong)</option>
                                                    </optgroup>
                                                    <optgroup label="Masalah Pembayaran">
                                                        <option value="pembayaran_belum_selesai">
                                                            Pembayaran belum selesai
                                                        </option>
                                                        <option value="pembayaran_tidak_valid">
                                                            Pembayaran tidak valid
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="Permintaan dari Pelanggan">
                                                        <option value="permintaan_pelanggan">
                                                            Pelanggan meminta pembatalan
                                                        </option>
                                                        <option value="pelanggan_ingin_mengubah_pesanan">
                                                            Pelanggan ingin mengganti atau mengubah pesanan
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="Masalah Pengiriman">
                                                        <option value="alamat_pengiriman_tidak_dapat_dijangkau">
                                                            Alamat pengiriman tidak dapat dijangkau
                                                        </option>
                                                        <option value="kendala_logistik">Kendala logistik</option>
                                                    </optgroup>
                                                    <optgroup label="Kesalahan Admin">
                                                        <option value="kesalahan_harga_produk">
                                                            Kesalahan harga pada produk
                                                        </option>
                                                    </optgroup>
                                                    <optgroup label="Alasan lainnya">
                                                        <option value="alasan_lainnya">Alasan lainnya</option>
                                                    </optgroup>
                                                </select>
                                                <x-form.input-error
                                                    :messages="$errors->get('cancelationReason')"
                                                    class="mt-2"
                                                />
                                            </div>
                                            @if ($cancelationReason === 'alasan_lainnya')
                                                <div class="mt-4">
                                                    <x-form.input-label value="Alasan Lainnya" for="other-reason" />
                                                    <textarea
                                                        wire:model.lazy="otherCancelationReason"
                                                        name="other-reason"
                                                        id="other-reason"
                                                        class="mt-1 block w-full rounded-lg border-neutral-300 px-4 py-3 pe-9 text-sm focus:border-primary focus:ring-primary"
                                                        placeholder="Masukkan alasan lainnya..."
                                                        required
                                                    ></textarea>
                                                    <x-form.input-error
                                                        :messages="$errors->get('otherCancelationReason')"
                                                        class="mt-2"
                                                    />
                                                </div>
                                            @endif

                                            <div class="mt-8 flex justify-end gap-4">
                                                <x-common.button variant="secondary" x-on:click="$dispatch('close')">
                                                    Batal
                                                </x-common.button>
                                                <x-common.button type="submit" variant="danger">
                                                    Batalkan Pesanan
                                                </x-common.button>
                                            </div>
                                        </form>
                                    </x-common.modal>
                                @endif
                            @endcan

                            @can('update', $order)
                                @if ($order->status === 'payment_received')
                                    <x-common.button
                                        wire:click="processOrder('{{ $order->id }}')"
                                        variant="primary"
                                        class="w-full md:w-fit"
                                        aria-label="Proses pesanan"
                                        wire:loading.attr="disabled"
                                        wire:target="processOrder('{{ $order->id }}')"
                                    >
                                        <span wire:loading.remove wire:target="processOrder('{{ $order->id }}')">
                                            Proses Pesanan
                                        </span>
                                        <span
                                            wire:loading.flex
                                            wire:target="processOrder('{{ $order->id }}')"
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
                                @endif
                            @endcan

                            @can('update', $order)
                                @if ($order->status === 'processing')
                                    <x-common.button
                                        variant="primary"
                                        class="w-full md:w-fit"
                                        aria-label="Kirim pesanan"
                                        x-on:click.prevent.stop="$dispatch('initiate-order-shipping', { 'id': '{{ $order->id }}' })"
                                        wire:loading.attr="disabled"
                                        wire:target.except="cancelationReason,shipmentTrackingNumber"
                                    >
                                        Kirim Pesanan
                                    </x-common.button>
                                    <x-common.modal
                                        name="confirm-order-shipping-{{ $order->id }}"
                                        :show="$errors->isNotEmpty()"
                                        focusable
                                    >
                                        <form wire:submit="shipOrder" class="flex flex-col items-center p-6">
                                            <div class="mb-4 rounded-full bg-primary-100 p-4" aria-hidden="true">
                                                <svg
                                                    class="size-16 shrink-0 text-primary"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        d="M3.375 4.5C2.339 4.5 1.5 5.34 1.5 6.375V13.5h12V6.375c0-1.036-.84-1.875-1.875-1.875h-8.25ZM13.5 15h-12v2.625c0 1.035.84 1.875 1.875 1.875h.375a3 3 0 1 1 6 0h3a.75.75 0 0 0 .75-.75V15Z"
                                                    />
                                                    <path
                                                        d="M8.25 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0ZM15.75 6.75a.75.75 0 0 0-.75.75v11.25c0 .087.015.17.042.248a3 3 0 0 1 5.958.464c.853-.175 1.522-.935 1.464-1.883a18.659 18.659 0 0 0-3.732-10.104 1.837 1.837 0 0 0-1.47-.725H15.75Z"
                                                    />
                                                    <path d="M19.5 19.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" />
                                                </svg>
                                            </div>
                                            <h2 class="mb-2 text-center text-black">Kirim Pesanan</h2>
                                            <p
                                                class="mb-4 text-center text-base font-medium tracking-tight text-black/70"
                                            >
                                                Silakan isi nomor resi yang diberikan oleh kurir ekspedisi pada inputan
                                                dibawah ini.
                                            </p>
                                            <div class="mb-8 flex w-full flex-col items-start">
                                                <x-form.input-label
                                                    value="Nomor Resi Pengiriman"
                                                    for="shipment-tracking-number"
                                                />
                                                <x-form.input
                                                    wire:model.lazy="shipmentTrackingNumber"
                                                    type="text"
                                                    id="shipment-tracking-number"
                                                    name="shipment-tracking-number"
                                                    class="mt-1 w-full"
                                                    placeholder="Isikan nomor resi pengiriman disini..."
                                                    minlength="5"
                                                    maxlength="50"
                                                    autocomplete="off"
                                                    required
                                                    :hasError="$errors->has('shipmentTrackingNumber')"
                                                />
                                                <x-form.input-error
                                                    :messages="$errors->get('shipmentTrackingNumber')"
                                                    class="mt-2"
                                                />
                                            </div>
                                            <div class="flex justify-end gap-4">
                                                <x-common.button variant="secondary" x-on:click="$dispatch('close')">
                                                    Batal
                                                </x-common.button>
                                                <x-common.button type="submit" variant="primary">
                                                    Kirim Pesanan
                                                </x-common.button>
                                            </div>
                                        </form>
                                    </x-common.modal>
                                @endif
                            @endcan

                            @can('update', $order)
                                @if ($order->status === 'shipping')
                                    <x-common.button
                                        variant="primary"
                                        class="w-full md:w-fit"
                                        aria-label="Selesaikan pesanan"
                                        x-on:click.prevent.stop="$dispatch('initiate-order-finishing', { 'id': '{{ $order->id }}' })"
                                        wire:loading.attr="disabled"
                                        wire:target.except="cancelationReason,shipmentTrackingNumber"
                                    >
                                        Selesaikan Pesanan
                                    </x-common.button>
                                    <x-common.modal
                                        name="confirm-order-finishing-{{ $order->id }}"
                                        :show="$errors->isNotEmpty()"
                                        focusable
                                    >
                                        <form wire:submit="finishOrder" class="flex flex-col items-center p-6">
                                            <div class="mb-4 rounded-full bg-primary-100 p-4">
                                                <svg
                                                    class="size-16 shrink-0 text-primary"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    aria-hidden="true"
                                                >
                                                    <path d="m16 16 2 2 4-4" />
                                                    <path
                                                        d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"
                                                    />
                                                    <path d="m7.5 4.27 9 5.15" />
                                                    <polyline points="3.29 7 12 12 20.71 7" />
                                                    <line x1="12" x2="12" y1="22" y2="12" />
                                                </svg>
                                            </div>
                                            <h2 class="mb-2 text-center text-black">Selesaikan Pesanan</h2>
                                            <p
                                                class="mb-4 text-center text-base font-medium tracking-tight text-black/70"
                                            >
                                                <strong class="text-black">
                                                    Pastikan bahwa pelanggan telah menerima produk yang dipesan
                                                </strong>
                                                sebelum anda menyelesaikan pesanan ini.
                                            </p>
                                            <p
                                                class="mb-8 text-center text-base font-medium tracking-tight text-black/70"
                                            >
                                                Anda dapat
                                                <strong class="text-black">
                                                    menghubungi nomor telefon yang tertera pada pesanan ini
                                                </strong>
                                                untuk mengkonfirmasi kepada pelanggan bahwa produk yang dipesan yang
                                                telah diterima.
                                            </p>
                                            <div class="flex justify-end gap-4">
                                                <x-common.button variant="secondary" x-on:click="$dispatch('close')">
                                                    Batal
                                                </x-common.button>
                                                <x-common.button type="submit" variant="primary">
                                                    Selesaikan Pesanan
                                                </x-common.button>
                                            </div>
                                        </form>
                                    </x-common.modal>
                                @endif
                            @endcan
                        </div>
                    </div>
                </footer>
            </article>
            {{ $this->orders->links() }}
        @empty
            <figure
                class="flex h-full flex-col items-center justify-center"
                wire:loading.class="opacity-50"
                wire:target="status, search, resetSearch"
            >
                <img
                    src="https://placehold.co/400"
                    class="mb-6 size-72 object-cover"
                    alt="Gambar ilustrasi pesanan tidak ditemukan"
                />
                <figcaption class="flex flex-col items-center">
                    <h2 class="mb-3 text-center !text-2xl text-black">
                        Pesanan

                        @if ($status !== 'all')
                            Dengan Status
                            @if ($status === 'waiting_payment')
                                Menunggu Pembayaran
                            @elseif ($status === 'payment_received')
                                Menunggu Diproses
                            @elseif ($status === 'processing')
                                Menunggu Dikirim
                            @elseif ($status === 'shipping')
                                Dalam Pengiriman
                            @elseif ($status === 'completed')
                                Berhasil
                            @elseif ($status === 'failed')
                                Gagal
                            @elseif ($status === 'canceled')
                                Dibatalkan
                            @endif
                        @endif

                        Tidak Ditemukan
                    </h2>
                    <p class="mb-8 text-center text-base font-normal tracking-tight text-black/70">
                        Seluruh data pesanan pelanggan

                        @if ($status !== 'all')
                            dengan status
                            @if ($status === 'waiting_payment')
                                "menunggu pembayaran"
                            @elseif ($status === 'payment_received')
                                "untuk diproses"
                            @elseif ($status === 'processing')
                                "untuk dikirim"
                            @elseif ($status === 'shipping')
                                "dalam pengiriman"
                            @elseif ($status === 'completed')
                                "berhasil"
                            @elseif ($status === 'failed')
                                "gagal"
                            @elseif ($status === 'canceled')
                                "dibatalkan"
                            @endif
                        @endif

                        akan ditampilkan disini.
                    </p>
                </figcaption>
            </figure>
        @endforelse
        <div
            class="absolute left-1/2 top-32 h-full -translate-x-1/2"
            wire:loading
            wire:target="status, search, resetSearch"
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
</div>
