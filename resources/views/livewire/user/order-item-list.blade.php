<?php

use App\Models\Order;
use App\Models\ProductReview;
use App\Models\Refund;
use App\Services\PaymentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    protected PaymentService $paymentService;

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
            'label' => 'Menunggu Diproses',
            'count' => 0,
        ],
        [
            'code' => 'processing',
            'label' => 'Menunggu Pengiriman',
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

    public string $cancelationReason = '';
    public string $otherCancelationReason = '';

    public function boot(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
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

        return auth()
            ->user()
            ->orders()
            ->with(['details.productVariant.product.images', 'payment.refund'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('order_number', 'like', '%' . $search . '%');
            })
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByDesc('created_at')
            ->paginate(5);
    }

    public function resetSearch()
    {
        $this->reset('search');
    }

    public function confirmCancelOrder(string $id)
    {
        $this->order = Order::find($id);

        if (! $this->order) {
            session()->flash('error', 'Pesanan tidak dapat ditemukan.');
            return $this->redirectIntended(route('orders.index'), navigate: true);
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
                'otherCancelationReason' => 'Alasan lainnya',
            ],
        );

        $order = $this->order;

        try {
            $this->authorize('cancel', $order);

            if (in_array($order->payment->status, ['paid', 'settled'])) {
                $this->authorize('create', Refund::class);
            }

            DB::transaction(function () use ($order, $validated) {
                if ($order->payment->status === 'unpaid') {
                    $order->payment->update([
                        'status' => 'expired',
                    ]);

                    $this->paymentService->expireInvoice($order->payment->xendit_invoice_id);
                } elseif (in_array($order->payment->status, ['paid', 'settled'])) {
                    $order->payment->update([
                        'status' => 'refunded',
                    ]);

                    Refund::create([
                        'payment_id' => $order->payment->id,
                    ]);
                }

                $cancelationReason = 'Dibatalkan oleh pelanggan: ';
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
            return $this->redirectIntended(route('orders.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('orders.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database Error During Order Cancellation', [
                'error_message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam membatalkan pesanan dengan nomor: ' .
                    $order->order_number .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('orders.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected Order Cancellation Error', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('orders.index'), navigate: true);
        }
    }

    public function finishOrder(string $id)
    {
        $order = Order::find($id);

        if (! $order) {
            session()->flash('error', 'Pesanan dengan nomor: ' . $order->order_number . ' tidak dapat ditemukan.');
            return $this->redirectIntended(route('orders.index'), navigate: true);
        }

        try {
            $this->authorize('update', $order);

            DB::transaction(function () use ($order) {
                $order->update([
                    'status' => 'completed',
                ]);
            });

            session()->flash('success', 'Pesanan dengan nomor: ' . $order->order_number . ' berhasil diselesaikan.');
            return $this->redirectIntended(route('orders.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('orders.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database Error During Order Completion', [
                'error_message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menyelesaikan pesanan dengan nomor: ' .
                    $order->order_number .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('orders.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected Order Cancellation Error', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('orders.index'), navigate: true);
        }
    }

    public function rateProducts(array $data)
    {
        $validator = Validator::make(
            $data,
            rules: [
                '*.product_variant_id' => 'required|exists:product_variants,id',
                '*.order_detail_id' => 'required|exists:order_details,id',
                '*.rating' => 'required|integer|between:1,5',
                '*.review' => 'nullable|string|min:5|max:255',
            ],
            attributes: [
                '*.product_variant_id' => 'Produk :position',
                '*.order_detail_id' => 'Detail pesanan :position',
                '*.rating' => 'Rating produk :position',
                '*.review' => 'Ulasan produk :position',
            ],
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();

            foreach ($errors as $key => $messages) {
                $this->addError($key, implode(', ', $messages));
            }

            return;
        }

        try {
            $this->authorize('create', new ProductReview());

            foreach ($validator->validated() as $review) {
                DB::transaction(function () use ($review) {
                    ProductReview::create([
                        'user_id' => auth()->id(),
                        'product_variant_id' => $review['product_variant_id'],
                        'order_detail_id' => $review['order_detail_id'],
                        'rating' => (int) $review['rating'],
                        'review' => $review['review'] !== '' ? $review['review'] : null,
                    ]);
                });
            }

            session()->flash('success', 'Produk berhasil dinilai, terimakasih atas penilaian anda.');
            return $this->redirectIntended(route('orders.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('orders.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database Error During Order Completion', [
                'error_message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            session()->flash('error', 'Terjadi kesalahan dalam menilai produk-produk dalam pesanan ini.');
            return $this->redirectIntended(route('orders.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected Order Cancellation Error', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('orders.index'), navigate: true);
        }
    }
}; ?>

<div>
    @if (in_array($status, ['waiting_payment', 'payment_received']))
        <div class="pointer-events-auto mb-4">
            <div
                class="rounded-md border border-yellow-400 bg-yellow-50 p-4 shadow-md"
                role="alert"
                aria-live="polite"
                aria-atomic="true"
            >
                <div class="flex items-center" role="presentation">
                    <div class="flex-shrink-0" aria-hidden="true">
                        <svg class="size-4 text-yellow-800" viewBox="0 0 20 20" fill="currentColor">
                            <title>Ikon notifikasi informasi</title>
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                            />
                        </svg>
                    </div>
                    <p role="heading" aria-level="2" class="ml-3 text-sm tracking-tight text-yellow-800">
                        <strong>Perhatian!</strong>
                        @if ($status === 'waiting_payment')
                            Pesanan yang belum dibayar akan dibatalkan secara otomatis dalam waktu
                            <strong>24 jam</strong>
                            setelah pesanan dibuat.
                        @elseif ($status === 'payment_received')
                            Pesanan yang sudah dibayar akan diproses dalam waktu
                            <strong>maksimal 1x24 jam</strong>
                            setelah pembayaran diterima. Jika pesanan anda tidak diproses, sistem akan membatalkan
                            pesanan anda dan permintaan refund akan diajukan secara otomatis.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

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
                            <span
                                @class([
                                    'inline-flex size-5 items-center justify-center whitespace-nowrap rounded-full text-xs font-medium tracking-tight',
                                    'bg-red-500 text-white' => in_array($orderStatus['code'], [
                                        'waiting_payment',
                                        'payment_received',
                                        'processing',
                                        'shipping',
                                    ]),
                                    'bg-inherit text-inherit' => in_array($orderStatus['code'], ['all', 'completed', 'failed', 'canceled']),
                                ])
                            >
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
            @can('view', $order)
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
                                @if (in_array($order->status, ['payment_received', 'processing', 'shipping']))
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
                                            @php
                                                $paidAt = Carbon\Carbon::parse($order->payment->paid_at);
                                                $minDate = $paidAt->copy()->addDays($order->estimated_shipping_min_days);
                                                $maxDate = $paidAt->copy()->addDays($order->estimated_shipping_max_days);
                                            @endphp

                                            Estimasi tiba:

                                            @if ($order->estimated_shipping_min_days === 0 && $order->estimated_shipping_max_days === 0)
                                                <time datetime="{{ $paidAt->toDateTimeString() }}">Hari Ini</time>
                                            @elseif ($order->estimated_shipping_min_days === $order->estimated_shipping_max_days)
                                                <time datetime="{{ $minDate->toDateTimeString() }}">
                                                    {{ formatDate($minDate->toDateTimeString()) }}
                                                </time>
                                            @else
                                                <time datetime="{{ $minDate->toDateTimeString() }}">
                                                    {{ formatDate($minDate->toDateTimeString()) }}
                                                </time>
                                                &mdash;
                                                <time datetime="{{ $maxDate->toDateTimeString() }}">
                                                    {{ formatDate($maxDate->toDateTimeString()) }}
                                                </time>
                                            @endif
                                        </p>
                                    </div>
                                @endif
                            </div>
                            @if ($order->status === 'shipping' && $order->shipment_tracking_number)
                                <div class="inline-flex">
                                    <svg
                                        class="me-2 size-5 shrink-0 text-black"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M8 2v4" />
                                        <path d="M12 2v4" />
                                        <path d="M16 2v4" />
                                        <rect width="16" height="18" x="4" y="4" rx="2" />
                                        <path d="M8 10h6" />
                                        <path d="M8 14h8" />
                                        <path d="M8 18h5" />
                                    </svg>
                                    <p class="text-sm font-medium tracking-tight text-black">
                                        Nomor resi pengiriman: {{ $order->shipment_tracking_number }}
                                    </p>
                                </div>
                            @endif
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
                                    Menunggu Diproses
                                @elseif ($order->status === 'processing')
                                    Menunggu Dikirim
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
                                    <x-common.dropdown-link href="#" x-on:click="event.stopPropagation()" wire:navigate>
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
                        </div>
                    </header>
                    <ul class="mb-8 space-y-4 p-4">
                        @foreach ($order->details as $item)
                            <li wire:key="{{ $item->id }}" class="flex items-start gap-x-4">
                                <a
                                    href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
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
                                        href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
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
                    <div
                        class="flex flex-col items-start justify-between gap-4 border-t border-t-neutral-300 p-4 md:flex-row md:items-center"
                    >
                        <div class="inline-flex items-center gap-x-2">
                            <p class="text-sm font-medium tracking-tight text-black/70">
                                Total:
                                <span class="ms-1 text-base font-semibold text-black">
                                    Rp {{ formatPrice($order->total_amount) }}
                                </span>
                            </p>
                            <x-common.tooltip
                                id="total-price-information"
                                text="Total harga sudah termasuk ongkos kirim dan diskon yang digunakan saat pesanan dibuat."
                                class="z-[60] w-72"
                            />
                        </div>
                        <div class="flex w-full flex-col items-center gap-2 md:w-fit md:flex-row">
                            @if ($order->payment->status === 'refunded' && $order->payment->refund()->exists())
                                <div class="inline-flex items-center gap-x-1">
                                    <svg
                                        class="size-5 shrink-0 text-black/70"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 0 1 .67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 1 1-.671-1.34l.041-.022ZM12 9a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <p class="text-sm font-medium tracking-tight text-black/70">
                                        Permintaan refund telah diajukan
                                    </p>
                                </div>
                            @endif

                            <x-common.button
                                :href="route('orders.show', ['orderNumber' => $order->order_number])"
                                variant="secondary"
                                class="w-full md:w-fit"
                                aria-label="Lihat detail pesanan"
                                wire:navigate
                            >
                                Detail Pesanan
                            </x-common.button>

                            @if (in_array($order->status, ['waiting_payment', 'payment_received', 'processing']))
                                <x-common.button
                                    variant="danger"
                                    class="w-full md:w-fit"
                                    aria-label="Batalkan pesanan"
                                    wire:click="confirmCancelOrder('{{ $order->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmCancelOrder('{{ $order->id }}')"
                                >
                                    <span wire:loading.remove wire:target="confirmCancelOrder('{{ $order->id }}')">
                                        Batalkan Pesanan
                                    </span>
                                    <span
                                        wire:loading.flex
                                        wire:target="confirmCancelOrder('{{ $order->id }}')"
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
                                        <p class="mb-4 text-center text-base font-medium tracking-tight text-black/70">
                                            Apakah Anda yakin ingin membatalkan pesanan dengan nomor pesanan:
                                            <strong class="text-black">{{ $order->order_number }}</strong>
                                            ? Proses ini
                                            <strong class="text-black">tidak dapat dibatalkan</strong>
                                            , dan status pesanan anda akan berubah menjadi
                                            <strong class="text-black">Dibatalkan</strong>
                                            .
                                        </p>
                                        <p class="mb-6 text-center text-sm font-medium tracking-tight text-red-800">
                                            <strong>Catatan:</strong>
                                            Jika anda telah melakukan pembayaran, permintaan refund akan diproses oleh
                                            admin dan memerlukan waktu sesuai ketentuan yang berlaku.
                                            <a href="#" class="inline-flex items-center gap-x-1 underline">
                                                Klik disini untuk mempelajari lebih lanjut
                                                <svg
                                                    class="size-3 shrink-0"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke-width="1.5"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25"
                                                    />
                                                </svg>
                                            </a>
                                        </p>
                                        <div class="flex w-full flex-col items-start">
                                            <x-form.input-label value="Alasan Pembatalan" for="cancelation-reason" />
                                            <select
                                                wire:model.lazy="cancelationReason"
                                                name="cancelation-reason"
                                                id="cancelation-reason"
                                                class="mt-1 block w-full rounded-lg border-neutral-300 px-4 py-3 pe-9 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                                required
                                            >
                                                <option value="" selected>Pilih Alasan Pembatalan Pesanan</option>
                                                <optgroup label="Alasan Terkait Produk">
                                                    <option value="kesalahan_pemesanan">Kesalahan Pemesanan</option>
                                                    <option value="harga_tidak_sesuai">Harga Tidak Sesuai</option>
                                                    <option value="kualitas_produk_tidak_memenuhi">
                                                        Kualitas Produk Tidak Memenuhi Harapan
                                                    </option>
                                                    <option value="produk_tidak_tersedia">Produk Tidak Tersedia</option>
                                                    <option value="produk_rusak_cacat">Produk Rusak atau Cacat</option>
                                                    <option value="masalah_dengan_varian_produk">
                                                        Masalah dengan Varian Produk
                                                    </option>
                                                </optgroup>
                                                <optgroup label="Alasan Terkait Pengiriman">
                                                    <option value="pengiriman_lama">Pengiriman Lama</option>
                                                    <option value="kesalahan_alamat_pengiriman">
                                                        Kesalahan Alamat Pengiriman
                                                    </option>
                                                    <option value="pesanan_tertunda_keterlambatan">
                                                        Pesanan Tertunda atau Keterlambatan
                                                    </option>
                                                    <option value="masalah_metode_pengiriman">
                                                        Masalah dengan Metode Pengiriman
                                                    </option>
                                                </optgroup>
                                                <optgroup label="Alasan Terkait Pembayaran">
                                                    <option value="metode_pembayaran_gagal">
                                                        Metode Pembayaran Gagal
                                                    </option>
                                                    <option value="diskon_tidak_diterima">
                                                        Diskon atau Penawaran Tidak Diterima
                                                    </option>
                                                </optgroup>
                                                <optgroup label="Alasan Terkait Kebijakan dan Pengalaman Pelanggan">
                                                    <option value="perubahan_pikiran">Perubahan Pikiran</option>
                                                    <option value="keinginan_untuk_ubah_pesanan">
                                                        Keinginan untuk Mengubah Pesanan
                                                    </option>
                                                    <option value="pemesanan_duplikat">Pemesanan Duplikat</option>
                                                    <option value="tidak_puas_dengan_layanan_pelanggan">
                                                        Tidak Puas dengan Layanan Pelanggan
                                                    </option>
                                                    <option value="pelanggan_tidak_dapat_menghubungi_toko">
                                                        Pelanggan Tidak Dapat Menghubungi Toko
                                                    </option>
                                                    <option value="masalah_teknis_situs_web">
                                                        Masalah Teknis dengan Situs Web
                                                    </option>
                                                    <option value="pelanggan_tidak_tahu_biaya_tambahan">
                                                        Pelanggan Tidak Tahu Tentang Biaya Tambahan
                                                    </option>
                                                </optgroup>
                                                <optgroup label="Alasan Lainnya">
                                                    <option value="alasan_lainnya">Alasan Lainnya</option>
                                                </optgroup>
                                            </select>
                                            <x-form.input-error
                                                :messages="$errors->get('cancelationReason')"
                                                class="mt-2"
                                            />
                                        </div>
                                        @if ($cancelationReason === 'alasan_lainnya')
                                            <div class="mt-4 flex w-full flex-col items-start">
                                                <x-form.input-label value="Alasan Lainnya" for="other-reason" />
                                                <x-form.textarea
                                                    wire:model.lazy="otherCancelationReason"
                                                    id="other-reason"
                                                    name="other-reason"
                                                    rows="3"
                                                    placeholder="Isikkan alasan pembatalan lainnya di sini..."
                                                    minlength="5"
                                                    maxlength="255"
                                                    required
                                                    :hasError="$errors->has('otherCancelationReason')"
                                                ></x-form.textarea>
                                                <x-form.input-error
                                                    :messages="$errors->get('otherCancelationReason')"
                                                    class="mt-2"
                                                />
                                            </div>
                                        @endif

                                        <div class="mt-8 flex w-full flex-col justify-end gap-4 md:flex-row">
                                            <x-common.button
                                                variant="secondary"
                                                x-on:click="$dispatch('close')"
                                                wire:loading.class="pointers-event-none !cursor-wait opacity-50"
                                                wire:target="cancelationReason, otherCancelationReason"
                                            >
                                                Batal
                                            </x-common.button>
                                            <x-common.button
                                                type="submit"
                                                variant="danger"
                                                wire:loading.attr="disabled"
                                                wire:target="cancelationReason, otherCancelationReason"
                                            >
                                                <span wire:loading.remove wire:target="cancelOrder">
                                                    Batalkan Pesanan
                                                </span>
                                                <span
                                                    wire:loading.flex
                                                    wire:target="cancelOrder"
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
                                    </form>
                                </x-common.modal>
                            @endif

                            @if ($order->status === 'waiting_payment')
                                <x-common.button
                                    :href="$order->payment->xendit_invoice_url"
                                    variant="primary"
                                    class="w-full md:w-fit"
                                    aria-label="Bayar pesanan"
                                >
                                    Bayar Pesanan
                                </x-common.button>
                            @elseif ($order->status === 'shipping')
                                <x-common.button
                                    variant="primary"
                                    class="w-full md:w-fit"
                                    aria-label="Selesaikan pesanan"
                                    x-on:click.prevent.stop="$dispatch('open-modal', 'confirm-order-completion-{{ $order->id }}')"
                                >
                                    Selesaikan Pesanan
                                </x-common.button>
                                <x-common.modal
                                    name="confirm-order-completion-{{ $order->id }}"
                                    :show="$errors->isNotEmpty()"
                                    focusable
                                >
                                    <div class="flex flex-col items-center p-6">
                                        <div class="mb-4 rounded-full bg-teal-100 p-4" aria-hidden="true">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="currentColor"
                                                class="size-16 text-teal-500"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                        <h2 class="mb-2 text-center text-black">Selesaikan Pesanan</h2>
                                        <p class="mb-4 text-center text-base font-medium tracking-tight text-black/70">
                                            Pastikan Anda telah menerima pesanan dan memeriksa kondisinya sebelum
                                            menyelesaikan transaksi. Dengan melanjutkan, Anda menyatakan bahwa pesanan
                                            telah diterima dengan baik.
                                        </p>
                                        <div class="mt-8 flex w-full flex-col justify-end gap-4 md:flex-row">
                                            <x-common.button
                                                variant="secondary"
                                                x-on:click="$dispatch('close')"
                                                wire:loading.class="pointers-event-none !cursor-wait opacity-50"
                                                wire:target="finishOrder('{{ $order->id }}')"
                                            >
                                                Batal
                                            </x-common.button>
                                            <x-common.button
                                                variant="primary"
                                                wire:click="finishOrder('{{ $order->id }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="finishOrder('{{ $order->id }}')"
                                            >
                                                <span
                                                    wire:loading.remove
                                                    wire:target="finishOrder('{{ $order->id }}')"
                                                >
                                                    Selesaikan Pesanan
                                                </span>
                                                <span
                                                    wire:loading.flex
                                                    wire:target="finishOrder('{{ $order->id }}')"
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
                            @elseif ($order->status === 'completed' && ! $order->hasBeenReviewed())
                                <x-common.button
                                    variant="primary"
                                    class="w-full md:w-fit"
                                    aria-label="Berikan penilaian"
                                    x-on:click.prevent.stop="$dispatch('open-modal', 'rate-{{ $order->id }}')"
                                >
                                    Berikan Penilaian
                                </x-common.button>
                                <x-common.modal name="rate-{{ $order->id }}" :show="$errors->isNotEmpty()" focusable>
                                    <div
                                        class="p-6"
                                        x-data="{
                                            ratings: [],
                                            saveRating(rating, review, productVariantId, orderDetailId) {
                                                const productIndex = this.ratings.findIndex(
                                                    (r) => r.product_variant_id === productVariantId,
                                                )

                                                if (productIndex !== -1) {
                                                    this.ratings[productIndex] = {
                                                        product_variant_id: productVariantId,
                                                        order_detail_id: orderDetailId,
                                                        rating,
                                                        review,
                                                    }
                                                } else {
                                                    this.ratings.push({
                                                        product_variant_id: productVariantId,
                                                        order_detail_id: orderDetailId,
                                                        rating,
                                                        review,
                                                    })
                                                }
                                            },
                                        }"
                                    >
                                        <h2 class="mb-3 !text-2xl leading-none text-black">Penilaian Produk</h2>
                                        <div class="flex h-full max-h-[28rem] w-full flex-col gap-y-3 overflow-y-auto">
                                            @foreach ($order->details as $item)
                                                <article
                                                    wire:key="{{ $item->id }}"
                                                    class="flex flex-col rounded-md border border-neutral-300 p-3 shadow-sm"
                                                    x-data="{
                                                        selectedRating: null,
                                                        review: '',
                                                        productVariantId: '{{ $item->productVariant->id }}',
                                                        orderDetailId: '{{ $item->id }}',
                                                    }"
                                                    x-init="
                                                        $watch('selectedRating', (value) =>
                                                            saveRating(value, review, productVariantId, orderDetailId),
                                                        )
                                                        $watch('review', (value) =>
                                                            saveRating(selectedRating, value, productVariantId, orderDetailId),
                                                        )
                                                    "
                                                >
                                                    <div class="flex items-center gap-x-4">
                                                        <a
                                                            href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
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
                                                        <div
                                                            class="flex h-20 w-full flex-col items-start justify-center"
                                                        >
                                                            <a
                                                                href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
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
                                                        </div>
                                                        <div
                                                            x-data="{ hoverIndex: null }"
                                                            class="ml-auto flex flex-col items-start gap-y-2"
                                                        >
                                                            <div class="flex items-center gap-x-1">
                                                                @for ($i = 0; $i < 5; $i++)
                                                                    <button
                                                                        type="button"
                                                                        x-on:click="selectedRating = {{ $i + 1 }}"
                                                                        x-on:mouseover="hoverIndex = {{ $i }}"
                                                                        x-on:mouseleave="hoverIndex = null"
                                                                    >
                                                                        <svg
                                                                            class="size-5 shrink-0 transition-colors"
                                                                            :class="selectedRating !== null && selectedRating > {{ $i }} || (hoverIndex !== null && hoverIndex >= {{ $i }}) ? 'fill-primary' : 'fill-neutral-200'"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            viewBox="0 0 24 24"
                                                                            fill="currentColor"
                                                                        >
                                                                            <path
                                                                                fill-rule="evenodd"
                                                                                d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                                                                                clip-rule="evenodd"
                                                                            />
                                                                        </svg>
                                                                    </button>
                                                                @endfor
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div x-show="selectedRating !== null" class="mt-4 w-full">
                                                        <div class="mb-1 flex items-center justify-between">
                                                            <x-form.input-label
                                                                :required="false"
                                                                for="product-{{ $loop->index }}-review"
                                                                value="Ulasan produk"
                                                            />
                                                            <span class="text-xs tracking-tight text-black/70">
                                                                (opsional)
                                                            </span>
                                                        </div>
                                                        <x-form.textarea
                                                            x-model="review"
                                                            id="product-{{ $loop->index }}-review"
                                                            name="product-{{ $loop->index }}-review"
                                                            rows="4"
                                                            placeholder="Tuliskan ulasan Anda di sini (opsional)"
                                                            minlength="10"
                                                            maxlength="255"
                                                            :hasError="$errors->has($loop->index . '.review')"
                                                        ></x-form.textarea>
                                                    </div>
                                                    <x-form.input-error
                                                        :messages="$errors->get($loop->index . '.rating')"
                                                        class="mt-2"
                                                    />
                                                    <x-form.input-error
                                                        :messages="$errors->get($loop->index . '.review')"
                                                        class="mt-2"
                                                    />
                                                </article>
                                            @endforeach
                                        </div>
                                        <div class="mt-8 flex w-full flex-col justify-end gap-4 md:flex-row">
                                            <x-common.button variant="secondary" x-on:click="$dispatch('close')">
                                                Batal
                                            </x-common.button>
                                            <x-common.button variant="primary" x-on:click="$wire.rateProducts(ratings)">
                                                <span wire:loading.remove>Berikan Penilaian</span>
                                                <span wire:loading.flex class="items-center gap-x-2">
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
                            @endif
                        </div>
                    </div>
                </article>
            @endcan
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
                                Sedang Diproses
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
                        Seluruh data pesanan Anda

                        @if ($status !== 'all')
                            dengan status
                            @if ($status === 'waiting_payment')
                                "menunggu pembayaran"
                            @elseif ($status === 'payment_received')
                                "menunggu diproses"
                            @elseif ($status === 'processing')
                                "Sedang diproses"
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
