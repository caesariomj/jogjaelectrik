<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    protected PaymentService $paymentService;

    public ?Order $order = null;
    public ?Payment $payment = null;

    #[Locked]
    public array $eWalletPaymentMethods = ['qris', 'gopay', 'shopeepay', 'dana', 'other_qris'];
    public array $bankTransferPaymentMethods = [
        'bca_va',
        'bni_va',
        'bri_va',
        'echannel',
        'permata_va',
        'cimb_va',
        'other_va',
    ];
    public $timeRemaining;
    public $endTime;
    public string $paymentToken;
    public bool $isPaid = false;

    public function boot(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function mount(?string $orderNumber = null)
    {
        if (! $orderNumber) {
            session()->flash('error', 'Anda harus menyertakan nomor pesanan anda untuk mengakses halaman ini.');
            return $this->redirectRoute('home', navigate: true);
        }

        $this->order = Order::with(['details', 'payment'])
            ->where('order_number', $orderNumber)
            ->first();

        if (! $this->order) {
            session()->flash('error', 'Pesanan dengan nomor ' . $orderNumber . ' tidak ditemukan.');
            return $this->redirectRoute('home', navigate: true);
        }

        // if (
        //     in_array($this->order->status, [
        //         'payment_received',
        //         'processing',
        //         'shipping',
        //         'completed',
        //         'failed',
        //         'canceled',
        //     ])
        // ) {
        //     return $this->redirectRoute('orders.index', navigate: true);
        // }

        $this->payment = $this->order->payment;
        $this->paymentToken = $this->payment->token;

        $this->setEndTime();
        $this->calculateTimeRemaining();

        if ($this->order->status === 'waiting_payment' && $this->timeRemaining === 0) {
            $this->order->update([
                'status' => 'failed',
                'cancelation_reason' => 'Dibatalkan oleh sistem: Batas waktu pembayaran telah terlewat.',
            ]);

            $this->payment->update([
                'status' => 'expire',
            ]);

            return $this->redirectRoute('orders.index', navigate: true);
        }
    }

    private function setEndTime()
    {
        if (in_array($this->payment->method, $this->eWalletPaymentMethods)) {
            $this->endTime = Carbon::parse($this->payment->created_at)->addMinutes(15);
        } elseif (in_array($this->payment->method, $this->bankTransferPaymentMethods)) {
            $this->endTime = Carbon::parse($this->payment->created_at)->addDay();
        }
    }

    private function calculateTimeRemaining()
    {
        $this->timeRemaining = Carbon::now()->diffInSeconds($this->endTime, false);

        if ($this->timeRemaining <= 0) {
            $this->timeRemaining = 0;
        }

        $this->timeRemaining = (int) $this->timeRemaining;
    }

    #[On('check-transaction-status')]
    public function checkAndUpdateTransactionStatus()
    {
        if ($this->timeRemaining > 0) {
            return;
        }

        try {
            $this->authorize('update', $this->order);

            $status = $this->paymentService->checkTransactionStatus($this->order->id);

            DB::transaction(function () use ($status) {
                if ($status->transaction_status === 'expire') {
                    $this->order->update([
                        'status' => 'failed',
                        'cancelation_reason' => 'Dibatalkan oleh sistem: Batas waktu pembayaran telah terlewat.',
                    ]);
                } elseif ($status->transaction_status === 'cancel') {
                    $this->order->update([
                        'status' => 'canceled',
                        'cancelation_reason' => 'Dibatalkan oleh admin.',
                    ]);
                } else {
                    $this->order->update([
                        'status' => 'failed',
                        'cancelation_reason' => 'Dibatalkan oleh sistem: Terjadi kesalahan yang tidak terduga.',
                    ]);
                }

                $this->payment->update([
                    'status' => $status->transaction_status,
                ]);
            });

            return $this->redirectRoute('orders.index', navigate: true);
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            session()->flash('error', $authException->getMessage());
            return $this->redirect(request()->header('Referer'), true);
        } catch (\Illuminate\Database\QueryException $queryException) {
            \Illuminate\Support\Facades\Log::error('Database error during transaction', [
                'error' => $queryException->getMessage(),
                'exception_trace' => $queryException->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');
            return $this->redirect(request()->header('Referer'), true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirect(request()->header('Referer'), navigate: true);
        }
    }

    #[On('payment-successful')]
    public function handlePaymentSuccess($referenceNumber)
    {
        try {
            $this->authorize('update', $this->order);

            DB::transaction(function () use ($referenceNumber) {
                $this->order->update([
                    'status' => 'payment_received',
                ]);

                $this->payment->update([
                    'status' => 'settlement',
                    'reference_number' => \Illuminate\Support\Facades\Crypt::encryptString($referenceNumber),
                ]);
            });

            return $this->redirectRoute('orders.index', navigate: true);
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            session()->flash('error', $authException->getMessage());
            return $this->redirect(request()->header('Referer'), true);
        } catch (\Illuminate\Database\QueryException $queryException) {
            \Illuminate\Support\Facades\Log::error('Database error during transaction', [
                'error' => $queryException->getMessage(),
                'exception_trace' => $queryException->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.');
            return $this->redirect(request()->header('Referer'), true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirect(request()->header('Referer'), navigate: true);
        }
    }

    #[On('payment-error')]
    public function handlePaymentFailure($error)
    {
        \Illuminate\Support\Facades\Log::error('Midtrans payment error', [
            'error_data' => $error,
        ]);

        $this->order->update([
            'status' => 'failed',
            'cancelation_reason' => 'Dibatalkan oleh sistem: Terjadi kesalahan yang tidak terduga.',
        ]);

        $this->payment->update([
            'status' => 'expire',
        ]);

        session()->flash('error', 'Terjadi kesalahan pada sistem pembayaran, silakan coba beberapa saat lagi.');
        return $this->redirectRoute('home', navigate: true);
    }
}; ?>

@section('title', 'Pesanan Berhasil')
<section
    x-data="{
        timeRemaining: @js($timeRemaining),
        timer: null,
        paymentToken: @js($paymentToken),
        startTimer() {
            if (this.timeRemaining > 0) {
                this.timer = setInterval(() => {
                    this.timeRemaining--
                    $wire.timeRemaining = this.timeRemaining
                }, 1000)
            }
        },
        pay() {
            if (this.paymentToken) {
                window.snap.pay(`${this.paymentToken}`, {
                    onSuccess: function (result) {
                        let referenceNumber = null

                        if (result.payment_type === 'echannel') {
                            referenceNumber = result.bill_key
                        } else if (result.payment_type === 'bank_transfer') {
                            if (result.va_numbers?.length) {
                                referenceNumber = result.va_numbers
                                    .map((va) => va.va_number)
                                    .join(', ')
                            } else if (result.permata_va_number) {
                                referenceNumber = result.permata_va_number
                            } else if (result.bca_va_number) {
                                referenceNumber = result.bca_va_number
                            }
                        }

                        if (referenceNumber !== null) {
                            $wire.dispatch('payment-successful', {
                                referenceNumber: referenceNumber,
                            })
                        }
                    },
                    onPending: function (result) {
                        alert('Transaction has been canceled or an error occurred.')
                        console.log('Error or cancellation:', result)
                    },
                    onError: function (result) {
                        $wire.dispatch('payment-error', {
                            error: result,
                        })
                    },
                    onClose: function () {
                        if (this.timeRemaining !== 0) {
                            $wire.dispatch('check-transaction-status')
                        }
                    },
                })
            }
        },
    }"
    class="mx-auto flex max-w-3xl flex-col items-center p-4 md:p-6"
>
    <section class="mb-6 w-full">
        <div class="-my-10 flex h-44 w-full items-center justify-center md:-my-14 md:h-56">
            <dotlottie-player
                autoplay
                background="transparent"
                class="h-full w-full"
                speed="1.5"
                src="https://lottie.host/2330d21a-2d8c-4224-a217-afb4e09c0684/ol6BOWowZA.json"
            ></dotlottie-player>
        </div>
        <h1 class="mb-2 text-center text-black">Terima kasih atas pesanan Anda!</h1>
        <p class="mb-2 text-center text-base font-medium tracking-tight text-black/70">
            Nomor pesanan:
            <span class="font-semibold text-black">{{ $order->order_number }}</span>
        </p>
        <p class="text-center text-base font-medium tracking-tight text-black/70">
            Silakan selesaikan pembayaran sebelum
            <span class="font-semibold text-red-800">{{ formatTimestamp($endTime) }} WIB</span>
            agar pesanan anda dapat segera kami proses.
        </p>
    </section>
    <section class="mb-12 w-full">
        <h2 class="mb-2 !text-3xl text-black">Ringkasan Pesanan:</h2>
        <div class="mb-4 flex flex-col space-y-4">
            @foreach ($order->details as $item)
                <article
                    wire:key="{{ $item->id }}"
                    class="flex items-start gap-x-4 border-b border-b-neutral-300 py-4"
                >
                    <a
                        href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
                        class="h-28 w-28 overflow-hidden rounded-lg bg-neutral-100"
                        wire:navigate
                    >
                        <img
                            src="{{ asset('uploads/product-images/' .$item->productVariant->product->images()->thumbnail()->first()->file_name,) }}"
                            alt="Gambar produk {{ strtolower($item->productVariant->product->name) }}"
                            class="aspect-square h-full w-full scale-100 object-cover brightness-100 transition-all ease-in-out hover:scale-105 hover:brightness-95"
                            loading="lazy"
                        />
                    </a>
                    <div class="flex flex-col items-start">
                        <a
                            href="{{ route('products.detail', ['slug' => $item->productVariant->product->slug]) }}"
                            wire:navigate
                        >
                            <h3 class="!text-lg text-black hover:text-primary">
                                {{ $item->productVariant->product->name }}
                            </h3>
                        </a>

                        @if ($item->productVariant->variant_sku)
                            <p class="mt-0.5 text-sm tracking-tight text-black">
                                {{ ucwords($item->productVariant->combinations->first()->variationVariant->variation->name) . ': ' . ucwords($item->productVariant->combinations->first()->variationVariant->name) }}
                            </p>
                        @endif

                        <p class="mt-2 inline-flex items-center text-sm font-medium tracking-tighter sm:text-base">
                            <span class="me-2 tracking-tight text-black/70">{{ $item->quantity }}</span>
                            x
                            <span class="ms-2 tracking-tight text-black">Rp {{ formatPrice($item->price) }}</span>
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
        <div>
            <dl class="grid grid-cols-2 gap-y-2">
                <dt class="text-base tracking-tight text-black/70">Subtotal:</dt>
                <dd class="text-base font-medium tracking-tight text-black">
                    Rp {{ formatPrice($order->subtotal_amount) }}
                </dd>

                @if ($order->discount_amount != 0.0)
                    <dt class="text-base tracking-tight text-black/70">Diskon:</dt>
                    <dd class="text-base font-medium tracking-tight text-teal-500">
                        Rp {{ formatPrice($order->discount_amount) }}
                    </dd>
                @endif

                <dt class="text-base tracking-tight text-black/70">Ongkos Kirim:</dt>
                <dd class="text-base font-medium tracking-tight text-black">
                    Rp {{ formatPrice($order->shipping_cost_amount) }}
                </dd>
                <dt class="text-base tracking-tight text-black/70">Total:</dt>
                <dd class="text-base font-medium tracking-tight text-black">
                    Rp {{ formatPrice($order->total_amount) }}
                </dd>
                <dt class="text-base tracking-tight text-black/70">Metode Pembayaran:</dt>
                <dd class="text-base font-medium tracking-tight text-black">
                    @if ($payment->method === 'qris')
                        QRIS
                    @elseif ($payment->method === 'gopay')
                        Gopay
                    @elseif ($payment->method === 'shopeepay')
                        ShopeePay
                    @elseif ($payment->method === 'dana')
                        DANA
                    @elseif ($payment->method === 'other_qris')
                        QRIS Lainnya
                    @elseif ($payment->method === 'bca_va')
                        BCA VA
                    @elseif ($payment->method === 'bni_va')
                        BNI VA
                    @elseif ($payment->method === 'bri_va')
                        BRIVA
                    @elseif ($payment->method === 'echannel')
                        MANDIRI BILL PAYMENT
                    @elseif ($payment->method === 'permata_va')
                        PERMATA VA
                    @elseif ($payment->method === 'cimb_va')
                        CIMB VA
                    @elseif ($payment->method === 'other_va')
                        VA Bank Lainnya
                    @endif
                </dd>
                <dt class="text-base tracking-tight text-black/70">Status Pesanan:</dt>
                <dd class="text-base font-medium tracking-tight text-black">Menunggu Pembayaran</dd>
            </dl>
        </div>
    </section>
    <section class="mb-6 w-full">
        <div class="flex w-full flex-col items-center justify-start gap-2 md:flex-row md:justify-center">
            <x-common.button :href="route('orders.index')" variant="secondary" class="w-full md:w-fit" wire:navigate>
                Lihat Pesanan
            </x-common.button>
            @can('pay', $payment)
                <template x-if="timeRemaining !== 0">
                    <x-common.button variant="primary" x-on:click="pay" class="w-full md:w-fit" x-cloak>
                        Bayar Sekarang
                    </x-common.button>
                </template>
            @endcan
        </div>
    </section>
    <section>
        <p class="text-center text-sm font-medium tracking-tight text-black/70">
            Jika Anda mengalami masalah mengenai pesanan Anda, jangan ragu untuk menghubungi kami.
        </p>
    </section>
</section>
