@extends('layouts.app')

@section('title', 'Detail Transaksi')

@section('content')
    <section class="container mx-auto flex max-w-md flex-row gap-6 px-6 py-6 md:max-w-[96rem] md:px-12">
        <x-user.sidebar />
        <section class="w-full md:w-5/6">
            <header class="mb-4 flex items-start">
                <x-common.button
                    :href="route('transactions.index')"
                    variant="secondary"
                    class="me-4 !p-2 md:hidden"
                    aria-label="Kembali ke halaman sebelumnya"
                    wire:navigate
                >
                    <svg
                        class="size-4 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </x-common.button>
                <h1 class="leading-none text-black">Detail Transaksi</h1>
            </header>
            <section class="mt-4">
                <h2 class="mb-2 text-2xl text-black">Informasi Utama Transaksi</h2>
                <dl class="grid grid-cols-1">
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">ID Transaksi</dt>
                        <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                            {{ $payment->id }}
                        </dd>
                    </div>
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nomor Pesanan</dt>
                        <dd class="w-full md:w-2/3">
                            <a
                                href="{{ route('orders.show', ['orderNumber' => $payment->order->order_number]) }}"
                                class="inline-flex items-center gap-x-1 font-medium tracking-tight text-black underline transition-colors hover:text-primary"
                                wire:navigate
                            >
                                {{ $payment->order->order_number }}
                                <svg
                                    class="size-3 shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25"
                                    />
                                </svg>
                            </a>
                        </dd>
                    </div>
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Status Pembayaran</dt>
                        <dd class="w-full md:w-2/3">
                            <span
                                @class([
                                    'inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-0.5 text-sm font-medium tracking-tight',
                                    'bg-yellow-100 text-yellow-800' => $payment->status === 'unpaid',
                                    'bg-teal-100 text-teal-800' =>
                                        in_array($payment->status, ['paid', 'settled']) ||
                                        (! is_null($payment->refund) && $payment->refund->status === 'succeeded'),
                                    'bg-red-100 text-red-800' =>
                                        $payment->status === 'expired' ||
                                        (! is_null($payment->refund) && in_array($payment->refund->status, ['rejected', 'failed'])),
                                    'bg-blue-100 text-blue-800' =>
                                        $payment->status === 'refunded' || (! is_null($payment->refund) && $payment->refund->status === 'pending'),
                                ])
                                role="status"
                            >
                                <span
                                    @class([
                                        'inline-block size-1.5 rounded-full',
                                        'bg-yellow-800' => $payment->status === 'unpaid',
                                        'bg-teal-800' =>
                                            in_array($payment->status, ['paid', 'settled']) ||
                                            (! is_null($payment->refund) && $payment->refund->status === 'succeeded'),
                                        'bg-red-800' =>
                                            $payment->status === 'expired' ||
                                            (! is_null($payment->refund) && in_array($payment->refund->status, ['rejected', 'failed'])),
                                        'bg-blue-800' =>
                                            $payment->status === 'refunded' || (! is_null($payment->refund) && $payment->refund->status === 'pending'),
                                    ])
                                ></span>
                                @if ($payment->status === 'unpaid')
                                    Belum Dibayar
                                @elseif (in_array($payment->status, ['paid', 'settled']))
                                    Berhasil
                                @elseif ($payment->status === 'expired')
                                    Kadaluarsa
                                @elseif ($payment->status === 'refunded' && $payment->refund->status === 'pending')
                                    Mengajukan Refund
                                @elseif ($payment->status === 'refunded' && $payment->refund->status === 'succeeded')
                                    Berhasil Direfund
                                @elseif ($payment->status === 'refunded' && $payment->refund->status === 'rejected')
                                    Refund Ditolak
                                @elseif ($payment->status === 'refunded' && $payment->refund->status === 'failed')
                                    Refund Gagal
                                @endif
                            </span>
                        </dd>
                    </div>

                    @if ($payment->method)
                        @php
                            $paymentMethod = null;
                            $paymentChannel = null;

                            if (str_contains($payment->method, 'bank_transfer_')) {
                                $paymentMethod = str_replace('bank_transfer_', '', $payment->method);
                                $paymentChannel = 'bank';
                            } elseif (str_contains($payment->method, 'ewallet_')) {
                                $paymentMethod = str_replace('ewallet_', '', $payment->method);
                                $paymentChannel = 'e-wallet';
                            }
                        @endphp

                        <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                            <dt class="w-full tracking-tight text-black/70 md:w-1/3">Metode Pembayaran</dt>
                            <dd class="inline-flex w-full items-center font-medium tracking-tight text-black md:w-2/3">
                                <img
                                    src="{{ asset('images/logos/payments/' . $paymentMethod . '.webp') }}"
                                    alt="Logo {{ strtoupper($paymentMethod) }}"
                                    class="me-2 h-auto w-10"
                                    loading="lazy"
                                />
                                {{ strtoupper($paymentMethod) }}
                                {{ str_contains($payment->method, 'bank_transfer_') ? ' VA' : '' }}
                            </dd>
                        </div>

                        @if (! is_null($paymentChannel) && $paymentChannel === 'bank')
                            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                                <dt class="w-full tracking-tight text-black/70 md:w-1/3">
                                    Nomor Referensi Virtual Account
                                </dt>
                                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                                    {{ $payment->reference_number }}
                                </dd>
                            </div>
                        @endif
                    @endif

                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Pembayaran</dt>
                        <dd class="inline-flex w-full flex-col gap-1 md:w-2/3">
                            <p
                                class="inline-flex items-center justify-between font-medium tracking-tight text-black/70"
                            >
                                Subtotal:
                                <span>Rp {{ formatPrice($payment->order->subtotal_amount) }}</span>
                            </p>

                            @if ($payment->order->discount_amount > 0)
                                <p
                                    class="inline-flex items-center justify-between font-medium tracking-tight text-black/70"
                                >
                                    Diskon:
                                    <span>- Rp {{ formatPrice($payment->order->discount_amount) }}</span>
                                </p>
                            @endif

                            <p
                                class="inline-flex items-center justify-between font-medium tracking-tight text-black/70"
                            >
                                Ongkos Kirim:
                                <span>+ Rp {{ formatPrice($payment->order->shipping_cost_amount) }}</span>
                            </p>
                            <p
                                class="mt-1 inline-flex items-center justify-between font-medium tracking-tight text-black"
                            >
                                Total Akhir:
                                <span>Rp {{ formatPrice($payment->order->total_amount) }}</span>
                            </p>
                        </dd>
                    </div>
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Transaksi Dibuat Pada</dt>
                        <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                            {{ formatTimestamp($payment->created_at) }}
                        </dd>
                    </div>
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Dibayar Pada</dt>
                        <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                            {{ $payment->paid_at ? formatTimestamp($payment->paid_at) : '-' }}
                        </dd>
                    </div>
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Transaksi Terakhir Diubah Pada</dt>
                        <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                            {{ formatTimestamp($payment->updated_at) }}
                        </dd>
                    </div>
                </dl>
            </section>

            @if ($payment->refund)
                <section class="mt-4">
                    <h2 class="mb-2 text-2xl text-black">Informasi Refund</h2>
                    <dl class="grid grid-cols-1">
                        <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                            <dt class="w-full tracking-tight text-black/70 md:w-1/3">Status Refund</dt>
                            <dd class="w-full md:w-2/3">
                                <span
                                    @class([
                                        'inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-0.5 text-sm font-medium tracking-tight',
                                        'bg-yellow-100 text-yellow-800' => $payment->refund->status === 'pending',
                                        'bg-teal-100 text-teal-800' => $payment->refund->status === 'succeeded',
                                        'bg-red-100 text-red-800' => in_array($payment->refund->status, ['rejected', 'failed']),
                                    ])
                                    role="status"
                                >
                                    <span
                                        @class([
                                            'inline-block size-1.5 rounded-full',
                                            'bg-yellow-800' => $payment->refund->status === 'pending',
                                            'bg-teal-800' => $payment->refund->status === 'succeeded',
                                            'bg-red-800' => in_array($payment->refund->status, ['rejected', 'failed']),
                                        ])
                                    ></span>
                                    @if ($payment->refund->status === 'pending')
                                        Menunggu Diproses
                                    @elseif ($payment->refund->status === 'succeeded')
                                        Berhasil
                                    @elseif ($payment->refund->status === 'rejected')
                                        Gagal
                                    @elseif ($payment->refund->status === 'failed')
                                        Gagal
                                    @endif
                                </span>
                            </dd>
                        </div>

                        @if (in_array($payment->refund->status, ['rejected', 'failed']))
                            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Pesan Gagal</dt>
                                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                                    {{ $payment->refund->status === 'rejected' ? $payment->refund->rejection_reason : 'Permintaan refund gagal. Silakan hubungi kami untuk bantuan lebih lanjut.' }}
                                </dd>
                            </div>
                        @endif

                        <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                            <dt class="w-full tracking-tight text-black/70 md:w-1/3">Refund Diajukan Pada</dt>
                            <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                                {{ formatTimestamp($payment->refund->created_at) }}
                            </dd>
                        </div>

                        @if ($payment->refund->status === 'succeeded')
                            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Direfund Pada</dt>
                                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                                    {{ $payment->refund->succeeded_at ? formatTimestamp($payment->refund->succeeded_at) : '-' }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </section>
            @endif

            <div class="mt-4 flex flex-col justify-end gap-4 md:flex-row">
                <x-common.button :href="route('transactions.index')" variant="secondary" wire:navigate>
                    Kembali
                </x-common.button>
            </div>
        </section>
    </section>
@endsection
