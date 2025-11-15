@extends('layouts.admin')

@section('title', 'Detail Pesanan ' . $order->order_number)

@section('content')
    <section>
        <header class="mb-4 flex items-start">
            <x-common.button
                :href="route('admin.orders.index')"
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
            <h1 class="leading-none text-black">Detail Pesanan &mdash; {{ $order->order_number }}</h1>
        </header>
        <section class="mb-4">
            <h2 class="mb-2 text-2xl text-black">Informasi Utama Pesanan</h2>
            <dl class="grid grid-cols-1">
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nomor Pesanan</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $order->order_number }}</dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Status Pesanan</dt>
                    <dd class="w-full md:w-2/3">
                        <span
                            @class([
                                'inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-0.5 text-sm font-medium tracking-tight',
                                'bg-yellow-100 text-yellow-800' => $order->status === 'waiting_payment',
                                'bg-blue-100 text-blue-800' => $order->status === 'payment_received',
                                'bg-teal-100 text-teal-800' => in_array($order->status, ['processing', 'shipping', 'completed']),
                                'bg-red-100 text-red-800' => in_array($order->status, ['failed', 'canceled']),
                            ])
                            role="status"
                        >
                            <span
                                @class([
                                    'inline-block size-1.5 rounded-full',
                                    'bg-yellow-800' => $order->status === 'waiting_payment',
                                    'bg-blue-800' => $order->status === 'payment_received',
                                    'bg-teal-800' => in_array($order->status, ['processing', 'shipping', 'completed']),
                                    'bg-red-800' => in_array($order->status, ['failed', 'canceled']),
                                ])
                            ></span>
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
                    </dd>
                </div>

                @if ($order->status === 'canceled')
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Alasan Pembatalan</dt>
                        <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                            {{ $order->cancelation_reason }}
                        </dd>
                    </div>
                @endif

                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Pesanan Dibuat Pada</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ formatTimestamp($order->created_at) }}
                    </dd>
                </div>

                @if ($order->payment && $order->payment->paid_at && $order->status !== 'canceled')
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Estimasi Pesanan Tiba</dt>
                        <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                            @php
                                $paidAt = Carbon\Carbon::parse($order->payment->paid_at);
                                $minDate = $paidAt->copy()->addDays($order->estimated_shipping_min_days);
                                $maxDate = $paidAt->copy()->addDays($order->estimated_shipping_max_days);
                            @endphp

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
                        </dd>
                    </div>
                @endif

                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Pembayaran</dt>
                    <dd class="inline-flex w-full flex-col gap-1 md:w-2/3">
                        <p class="inline-flex items-center justify-between font-medium tracking-tight text-black/70">
                            Subtotal:
                            <span>Rp {{ formatPrice($order->subtotal_amount) }}</span>
                        </p>

                        @if ($order->discount_amount)
                            <p
                                class="inline-flex items-center justify-between font-medium tracking-tight text-black/70"
                            >
                                Diskon:
                                <span>- Rp {{ formatPrice($order->discount_amount) }}</span>
                            </p>
                        @endif

                        <p class="inline-flex items-center justify-between font-medium tracking-tight text-black/70">
                            Ongkos Kirim:
                            <span>+ Rp {{ formatPrice($order->shipping_cost_amount) }}</span>
                        </p>
                        <p class="mt-1 inline-flex items-center justify-between font-medium tracking-tight text-black">
                            Total Akhir:
                            <span>Rp {{ formatPrice($order->total_amount) }}</span>
                        </p>
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Catatan Pesanan</dt>
                    <dd
                        @class([
                            'w-full font-medium tracking-tight text-black md:w-2/3',
                            'not-italic' => ! $order->note,
                            'italic' => $order->note,
                        ])
                    >
                        {{ $order->note ?? 'Tidak ada catatan' }}
                    </dd>
                </div>
            </dl>
        </section>

        @if ($order->payment)
            <section class="mb-4">
                <h2 class="mb-2 text-2xl text-black">Informasi Pembayaran</h2>
                <dl class="grid grid-cols-1">
                    @if ($order->payment->method)
                        <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                            <dt class="w-full tracking-tight text-black/70 md:w-1/3">Metode Pembayaran</dt>
                            <dd class="inline-flex w-full items-center font-medium tracking-tight text-black md:w-2/3">
                                @php
                                    $paymentMethod = null;

                                    if (str_contains($order->payment->method, 'bank_transfer_')) {
                                        $paymentMethod = str_replace('bank_transfer_', '', $order->payment->method);
                                    } elseif (str_contains($order->payment->method, 'ewallet_')) {
                                        $paymentMethod = str_replace('ewallet_', '', $order->payment->method);
                                    }
                                @endphp

                                <img
                                    src="{{ asset('images/logos/payments/' . $paymentMethod . '.webp') }}"
                                    alt="Logo {{ strtoupper($paymentMethod) }}"
                                    class="me-2 h-auto w-10"
                                    loading="lazy"
                                />
                                {{ strtoupper($paymentMethod) }}
                                {{ str_contains($order->payment->method, 'bank_transfer_') ? ' VA' : '' }}
                            </dd>
                        </div>
                    @endif

                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Status Pembayaran</dt>
                        @if ($order->source === 'offline')
                            <dd class="w-full font-medium tracking-tight text-black md:w-2/3">Penjualan offline</dd>
                        @else
                            <dd class="w-full md:w-2/3">
                                <span
                                    @class([
                                        'inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-0.5 text-sm font-medium tracking-tight',
                                        'bg-yellow-100 text-yellow-800' => $order->payment->status === 'unpaid',
                                        'bg-teal-100 text-teal-800' => in_array($order->payment->status, ['paid', 'settled']),
                                        'bg-red-100 text-red-800' => $order->payment->status === 'expired',
                                        'bg-blue-100 text-blue-800' => $order->payment->status === 'refunded',
                                    ])
                                    role="status"
                                >
                                    <span
                                        @class([
                                            'inline-block size-1.5 rounded-full',
                                            'bg-yellow-800' => $order->payment->status === 'unpaid',
                                            'bg-teal-800' => in_array($order->payment->status, ['paid', 'settled']),
                                            'bg-red-800' => $order->payment->status === 'expired',
                                            'bg-blue-800' => $order->payment->status === 'refunded',
                                        ])
                                    ></span>
                                    @if ($order->payment->status === 'unpaid')
                                        Belum Dibayar
                                    @elseif (in_array($order->payment->status, ['paid', 'settled']))
                                        Berhasil
                                    @elseif ($order->payment->status === 'expired')
                                        Kadaluarsa
                                    @elseif ($order->payment->status === 'refunded')
                                        Mengajukan Refund
                                    @endif
                                </span>
                            </dd>
                        @endif
                    </div>

                    @if ($order->payment->reference_number)
                        <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                            <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nomor Virtual Account</dt>
                            <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                                {{ $order->payment->reference_number }}
                            </dd>
                        </div>
                    @endif

                    @if ($order->payment->paid_at)
                        <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                            <dt class="w-full tracking-tight text-black/70 md:w-1/3">Pesanan Dibayar Pada</dt>
                            <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                                {{ formatTimestamp($order->payment->paid_at) }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>
        @endif

        @if ($order->payment && $order->payment->status === 'refunded' && $order->refund)
            <section class="mb-4">
                <h2 class="mb-2 text-2xl text-black">Informasi Refund</h2>
                <dl class="grid grid-cols-1">
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Status Refund</dt>
                        <dd class="w-full md:w-2/3">
                            <span
                                @class([
                                    'inline-flex items-center gap-x-1.5 rounded-full px-2.5 py-0.5 text-sm font-medium tracking-tight',
                                    'bg-yellow-100 text-yellow-800' => $order->refund->status === 'pending',
                                    'bg-teal-100 text-teal-800' => in_array($order->refund->status, ['approved', 'succeeded']),
                                    'bg-red-100 text-red-800' => in_array($order->refund->status, ['failed', 'rejected']),
                                ])
                                role="status"
                            >
                                <span
                                    @class([
                                        'inline-block size-1.5 rounded-full',
                                        'bg-yellow-800' => $order->refund->status === 'pending',
                                        'bg-teal-800' => in_array($order->refund->status, ['approved', 'succeeded']),
                                        'bg-red-800' => in_array($order->refund->status, ['failed', 'rejected']),
                                    ])
                                ></span>
                                @if ($order->refund->status === 'pending')
                                    Menunggu Diproses
                                @elseif ($order->refund->status === 'approved')
                                    Disetujui
                                @elseif ($order->refund->status === 'succeeded')
                                    Berhasil
                                @elseif ($order->refund->status === 'failed')
                                    Gagal
                                @elseif ($order->refund->status === 'rejected')
                                    Ditolak
                                @endif
                            </span>
                        </dd>
                    </div>
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Refund Diajukan Pada</dt>
                        <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                            {{ formatTimestamp($order->refund->created_at) }}
                        </dd>
                    </div>

                    @if ($order->refund->succeeded_at)
                        <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                            <dt class="w-full tracking-tight text-black/70 md:w-1/3">Direfund Pada</dt>
                            <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                                {{ formatTimestamp($order->refund->succeeded_at) }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>
        @endif

        <section class="mb-4">
            <h2 class="mb-2 text-2xl text-black">Informasi Pengiriman</h2>
            <dl class="grid grid-cols-1">
                @if ($order->shipping_courier)
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        @php
                            [$courier, $service] = explode('-', $order->shipping_courier);
                        @endphp

                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Ekspedisi dan Layanan Kurir</dt>
                        <dd class="inline-flex w-full items-center font-medium tracking-tight text-black md:w-2/3">
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
                    </div>
                @endif

                @if ($order->shipment_tracking_number)
                    <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nomor Resi Pengiriman</dt>
                        <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                            {{ strtoupper($order->shipment_tracking_number) }}
                        </dd>
                    </div>
                @endif

                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Alamat Pengiriman</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ $order->shipping_address ?? '-' }}
                    </dd>
                </div>
            </dl>
        </section>
        <section class="mb-4">
            <h2 class="mb-2 text-2xl text-black">Informasi Pelanggan</h2>
            <dl class="grid grid-cols-1">
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nama Pelanggan</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ $order->user->name ?? '-' }}
                    </dd>
                </div>
            </dl>
            <dl class="grid grid-cols-1">
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nomor Telefon</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ $order->user->phone_number ? $order->user->phone_number : '-' }}
                    </dd>
                </div>
            </dl>
            <dl class="grid grid-cols-1">
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Alamat Email</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ $order->user->email ?? '-' }}
                    </dd>
                </div>
            </dl>
        </section>
        <section class="mb-4">
            <h2 class="mb-2 text-2xl text-black">Rincian Produk</h2>
            <ul class="mb-8 space-y-4 py-4">
                @foreach ($order->details as $item)
                    <li
                        wire:key="{{ $item->id }}"
                        class="flex items-start gap-x-4 rounded-md border border-neutral-300 bg-white p-2 shadow-sm"
                    >
                        <a
                            href="{{ route('admin.products.show', ['slug' => $item->slug]) }}"
                            class="size-20 shrink-0 overflow-hidden rounded-lg bg-neutral-100"
                            wire:navigate
                        >
                            <img
                                src="{{ asset('storage/uploads/product-images/' . $item->thumbnail) }}"
                                alt="Gambar produk {{ strtolower($item->name) }}"
                                class="aspect-square h-full w-20 scale-100 object-cover brightness-100 transition-all ease-in-out hover:scale-105 hover:brightness-95"
                                loading="lazy"
                            />
                        </a>
                        <div class="flex h-20 w-full flex-col items-start">
                            <a
                                href="{{ route('admin.products.show', ['slug' => $item->slug]) }}"
                                class="mb-0.5"
                                wire:navigate
                            >
                                <h3 class="!text-base text-black hover:text-primary">
                                    {{ $item->name }}
                                </h3>
                            </a>

                            @if ($item->variation && $item->variant)
                                <p class="mb-2 text-sm tracking-tight text-black">
                                    {{ ucwords($item->variation) . ': ' . ucwords($item->variant) }}
                                </p>
                            @endif

                            <p
                                class="inline-flex items-center text-sm font-medium tracking-tighter text-black/70 sm:text-base"
                            >
                                <span class="me-2">{{ $item->quantity }}</span>
                                x
                                <span class="ms-2 tracking-tight text-black">Rp {{ formatPrice($item->price) }}</span>
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
        <div class="flex flex-col items-center gap-4 md:flex-row md:justify-end">
            <x-common.button
                :href="route('admin.orders.index')"
                variant="secondary"
                class="w-full md:w-fit"
                wire:navigate
            >
                Kembali
            </x-common.button>
        </div>
    </section>
@endsection
