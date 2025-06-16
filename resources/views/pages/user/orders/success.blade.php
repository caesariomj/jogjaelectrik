@extends('layouts.app')

@section('title', 'Pesanan Berhasil')

@section('content')
    <section class="container mx-auto max-w-md px-6 py-6 md:max-w-[96rem] md:px-12">
        <section class="w-full shrink">
            <h1 class="mb-6 text-black">Pesanan Berhasil</h1>
            <div class="flex flex-col gap-4 lg:flex-row lg:gap-6">
                <div class="w-full flex-1 space-y-6 lg:w-2/3">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-green-100 p-2">
                            <svg
                                class="h-6 w-6 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-black/70">
                                Pesanan
                                <span class="font-medium">{{ $order->order_number }}</span>
                            </p>
                            <h3 class="text-lg font-semibold text-black">Terima kasih, {{ $order->user->name }}!</h3>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-1.5">
                            <h3 class="mb-1.5 text-lg font-semibold text-black">Detail Pesanan</h3>
                            <p class="text-base font-medium tracking-tight text-black/70">
                                Nomor pesanan :
                                <span class="text-black">{{ $order->order_number }}</span>
                            </p>
                            <p class="text-base font-medium tracking-tight text-black/70">
                                Pesanan dibuat pada :
                                <span class="text-black">{{ formatTimestamp($order->created_at) }}</span>
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="mb-1.5 text-lg font-semibold text-black">Alamat Pengiriman</h3>
                            <p class="text-base font-medium tracking-tight text-black">
                                {{ $order->shipping_address }}
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="mb-1.5 text-lg font-semibold text-black">Metode Pembayaran</h3>
                            @php
                                $paymentMethod = str_replace(['ewallet_', 'bank_transfer_'], '', $order->payment->method);
                            @endphp

                            <div class="flex items-center gap-x-2">
                                <div class="h-8 w-14 rounded-md border border-neutral-300 px-2 py-1">
                                    <img
                                        src="{{ asset('images/logos/payments/' . $paymentMethod . '.webp') }}"
                                        alt="Logo {{ strtoupper($paymentMethod) }}"
                                        title="{{ strtoupper($paymentMethod) }}"
                                        class="h-full w-full object-contain"
                                        loading="lazy"
                                    />
                                </div>
                                <p class="text-base font-medium tracking-tight text-black">
                                    {{ strtoupper($paymentMethod) }}
                                </p>
                            </div>

                            @if ($order->payment->reference_number)
                                <p class="text-base font-medium tracking-tight text-black/70">
                                    Nomor referensi pembayaran :
                                    <span class="text-black">{{ $order->payment->reference_number }}</span>
                                </p>
                            @endif

                            <p class="text-base font-medium tracking-tight text-black/70">
                                Pesanan dibayar pada :
                                <span class="text-black">{{ formatTimestamp($order->payment->paid_at) }}</span>
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="mb-1.5 text-lg font-semibold text-black">Kurir Ekspedisi</h3>

                            @php
                                [$courier, $courierService] = explode('-', $order->shipping_courier);
                            @endphp

                            <div class="flex items-center gap-x-2">
                                <div class="h-8 w-14 rounded-md border border-neutral-300 px-2 py-1">
                                    <img
                                        src="{{ asset('images/logos/shipping/' . $courier . '.webp') }}"
                                        alt="Logo {{ strtoupper($courier) }}"
                                        title="{{ strtoupper($courier) }}"
                                        class="h-full w-full object-contain"
                                        loading="lazy"
                                    />
                                </div>
                                <p class="text-base font-medium tracking-tight text-black">
                                    {{ strtoupper($courier) }}
                                </p>
                            </div>
                            <p class="text-base font-medium tracking-tight text-black/70">
                                Layanan kurir ekspedisi :
                                <span class="text-black">{{ strtoupper($courierService) }}</span>
                            </p>
                            <p class="text-base font-medium tracking-tight text-black/70">
                                Estimasi tiba:
                                @if ($order->estimated_shipping_min_days === 0 && $order->estimated_shipping_max_days === 0)
                                    <span class="text-black">Hari Ini</span>
                                @elseif ($order->estimated_shipping_min_days === $order->estimated_shipping_max_days)
                                    <span class="text-black">{{ $order->estimated_shipping_max_days }} Hari</span>
                                @else
                                    <span class="text-black">
                                        {{ $order->estimated_shipping_min_days }}
                                    </span>
                                    &dash;
                                    <span class="text-black">{{ $order->estimated_shipping_max_days }} Hari</span>
                                @endif
                            </p>
                        </div>
                        <div class="space-y-1.5">
                            <h3 class="mb-1.5 text-lg font-semibold text-black">Catatan Pesanan</h3>
                            <p class="text-base font-medium italic tracking-tight text-black">{{ $order->note }}</p>
                        </div>
                    </div>
                    <p class="text-base tracking-tight text-black/70">
                        Anda dapat mencantumkan nomor pesanan diatas untuk menanyakan pesanan Anda ke kontak kami. Mohon
                        beri kami waktu
                        <span class="font-semibold text-black">± 1-2 hari</span>
                        untuk memproses pesanan Anda. Jika pesanan belum kami proses dan sudah melebihi batas waktu
                        tersebut maka sistem akan secara otomatis
                        <span class="font-semibold text-black">membatalkan & mengajukan permintaan refund</span>
                        ke kami. Dan jika permintaan refund Anda belum diproses, silakan
                        <span class="font-semibold text-black">menghubungi</span>
                        kontak kami. Terima kasih atas pengertian Anda!
                    </p>
                </div>
                <aside
                    class="relative h-full w-full rounded-md border border-neutral-300 py-4 shadow-md lg:sticky lg:top-20 lg:w-1/3"
                >
                    <h2 class="px-4 !text-2xl text-black">Pesanan Anda</h2>
                    <hr class="my-4 border-neutral-300" />
                    <ul class="space-y-4 px-4 pb-4">
                        @foreach ($order->details as $item)
                            <li class="flex items-center gap-4">
                                <img
                                    src="{{ asset('storage/uploads/product-images/' . $item->thumbnail) }}"
                                    class="h-14 w-14 rounded border object-cover"
                                    alt="Hoodie"
                                />
                                <div class="flex-1 text-sm">
                                    <p class="text-lg font-medium tracking-tight text-black">{{ $item->name }}</p>
                                    @if ($item->variation && $item->variant)
                                        <p class="text-sm text-black/70">
                                            {{ ucwords($item->variation) . ' : ' . ucwords($item->variant) }}
                                        </p>
                                    @endif
                                </div>
                                <p class="text-sm tracking-tight text-black/70">{{ $item->quantity }}x</p>
                                <p class="font-medium tracking-tight text-black">Rp {{ formatPrice($item->price) }}</p>
                            </li>
                        @endforeach
                    </ul>
                    <hr class="my-4 border-neutral-300" />
                    <dl class="grid grid-cols-2 gap-y-2 px-4">
                        <dt class="mb-1 text-start tracking-tight text-black/70">Subtotal</dt>
                        <dd class="mb-1 text-end font-medium tracking-tight text-black">
                            Rp {{ formatPrice($order->subtotal_amount) }}
                        </dd>
                        <dt class="mb-1 text-start tracking-tight text-black/70">Potongan Diskon</dt>
                        <dd
                            @class([
                                'mb-1 text-end font-medium tracking-tight',
                                'text-black' => $order->discount_amount <= 0,
                                'text-teal-500' => $order->discount_amount > 0,
                            ])
                        >
                            - Rp {{ $order->discount_amount > 0 ? formatPrice($order->discount_amount) : '0' }}
                        </dd>
                        <dt class="mb-1 text-start tracking-tight text-black/70">Biaya Pengiriman</dt>
                        <dd class="mb-1 text-end font-medium tracking-tight text-black">
                            Rp {{ formatPrice($order->shipping_cost_amount) }}
                        </dd>
                    </dl>
                    <hr class="my-4 border-neutral-300" />
                    <dl class="grid grid-cols-2 px-4">
                        <dt class="text-start tracking-tight text-black/70">Total</dt>
                        <dd class="text-end font-semibold tracking-tight text-black">
                            Rp
                            {{ formatPrice($order->total_amount) }}
                        </dd>
                    </dl>
                    <hr class="my-4 border-neutral-300" />
                    <div class="flex flex-col gap-4 px-4">
                        <x-common.button :href="route('home')" class="w-full" variant="primary" wire:navigate>
                            Kembali ke Beranda
                        </x-common.button>
                        <x-common.button
                            :href="route('orders.show', ['orderNumber' => $order->order_number])"
                            class="w-full"
                            variant="secondary"
                            wire:navigate
                        >
                            Lihat Detail Pesanan
                        </x-common.button>
                    </div>
                </aside>
            </div>
        </section>
    </section>
@endsection
