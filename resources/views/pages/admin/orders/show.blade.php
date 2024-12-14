@extends('layouts.admin')

@section('title', 'Detail Pesanan ' . $order->order_number)

@section('content')
    <section>
        <h1 class="mb-4 text-black">Detail Pesanan &mdash; {{ $order->order_number }}</h1>
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
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Pesanan Dibuat Pada</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ formatTimestamp($order->created_at) }}
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Metode Pembayaran</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        @if ($order->payment->method === 'qris')
                            QRIS
                        @elseif ($order->payment->method === 'gopay')
                            Gopay
                        @elseif ($order->payment->method === 'shopeepay')
                            ShopeePay
                        @elseif ($order->payment->method === 'dana')
                            DANA
                        @elseif ($order->payment->method === 'other_qris')
                            QRIS Lainnya
                        @elseif ($order->payment->method === 'bca_va')
                            BCA VA
                        @elseif ($order->payment->method === 'bni_va')
                            BNI VA
                        @elseif ($order->payment->method === 'bri_va')
                            BRIVA
                        @elseif ($order->payment->method === 'echannel')
                            MANDIRI BILL PAYMENT
                        @elseif ($order->payment->method === 'permata_va')
                            PERMATA VA
                        @elseif ($order->payment->method === 'cimb_va')
                            CIMB VA
                        @elseif ($order->payment->method === 'other_va')
                            VA Bank Lainnya
                        @endif
                    </dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Pembayaran</dt>
                    <dd class="inline-flex w-full flex-col gap-1 md:w-2/3">
                        <p class="inline-flex items-center justify-between font-medium tracking-tight text-black/70">
                            Subtotal:
                            <span>Rp {{ formatPrice($order->subtotal_amount) }}</span>
                        </p>

                        @if ($order->discount_amount > 0)
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
                        <p class="inline-flex items-center justify-between font-medium tracking-tight text-black">
                            Total Akhir:
                            <span>Rp {{ formatPrice($order->total_amount) }}</span>
                        </p>
                    </dd>
                </div>
            </dl>
        </section>
        <section class="mb-4">
            <h2 class="mb-2 text-2xl text-black">Informasi Pelanggan</h2>
            <dl class="grid grid-cols-1">
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nama Pelanggan</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $order->user->name }}</dd>
                </div>
            </dl>
            <dl class="grid grid-cols-1">
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nomor Telefon</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ '0' . \Illuminate\Support\Facades\Crypt::decryptString($order->user->phone_number) }}
                    </dd>
                </div>
            </dl>
            <dl class="grid grid-cols-1">
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Alamat Email</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $order->user->email }}</dd>
                </div>
            </dl>
        </section>
        <section class="mb-4">
            <h2 class="mb-2 text-2xl text-black">Rincian Produk</h2>
            <ul class="mb-8 space-y-4 p-4">
                @foreach ($order->details as $item)
                    <li wire:key="{{ $item->id }}" class="flex items-start gap-x-4">
                        <a
                            href="{{ route('admin.products.show', ['slug' => $item->productVariant->product->slug]) }}"
                            class="size-20 shrink-0 overflow-hidden rounded-lg bg-neutral-100"
                            wire:navigate
                        >
                            <img
                                src="{{ asset('uploads/product-images/' .$item->productVariant->product->images()->thumbnail()->first()->file_name,) }}"
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
                                <span class="ms-2 tracking-tight text-black">Rp {{ formatPrice($item->price) }}</span>
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
        <section class="mb-4">
            <h2 class="mb-2 text-2xl text-black">Detail Pengiriman</h2>
            <dl class="grid grid-cols-1">
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
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Alamat Pengiriman</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ \Illuminate\Support\Facades\Crypt::decryptString($order->shipping_address) }}
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
    </section>
@endsection
