@extends('layouts.admin')

@section('title', 'Detail Refund ' . $refund->id)

@section('content')
    <section>
        <header class="mb-4 flex items-start">
            <x-common.button
                :href="route('admin.refunds.index')"
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
            <h1 class="leading-none text-black">Detail Refund &mdash; {{ $refund->id }}</h1>
        </header>
        <dl class="mb-4 grid grid-cols-1">
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nomor Pesanan</dt>
                <dd class="w-full md:w-2/3">
                    <a
                        href="{{ route('admin.orders.show', ['orderNumber' => $refund->order->order_number]) }}"
                        class="inline-flex items-center gap-x-1 font-medium tracking-tight text-black underline transition-colors hover:text-primary"
                        wire:navigate
                    >
                        {{ $refund->order->order_number }}
                        <svg
                            class="size-3 shrink-0"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
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
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Belanja</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    Rp {{ formatPrice($refund->order->total_amount) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Metode Pembayaran</dt>
                <dd class="inline-flex w-full items-center gap-x-2 font-medium tracking-tight text-black md:w-2/3">
                    @php
                        if (str_contains($refund->payment->method, 'bank_transfer_')) {
                            $paymentType = 'Transfer Bank';
                            $paymentMethod = str_replace('bank_transfer_', '', $refund->payment->method);
                        } elseif (str_contains($refund->payment->method, 'ewallet_')) {
                            $paymentType = 'E-Wallet';
                            $paymentMethod = str_replace('ewallet_', '', $refund->payment->method);
                        }
                    @endphp

                    <img
                        src="{{ asset('images/logos/payments/' . $paymentMethod . '.webp') }}"
                        alt="Logo {{ strtoupper($paymentMethod) }}"
                        class="h-auto w-10"
                        loading="lazy"
                    />
                    {{ ucwords($paymentType . ' - ' . $paymentMethod) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Status</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    @if ($refund->status === 'pending')
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium tracking-tight text-yellow-800"
                        >
                            <span class="inline-block size-1.5 rounded-full bg-yellow-800"></span>
                            Menunggu Diproses
                        </span>
                    @elseif (in_array($refund->status, ['approved', 'succeeded']))
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                        >
                            <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                            {{ $refund->status === 'approved' ? 'Disetujui' : 'Berhasil' }}
                        </span>
                    @elseif (in_array($refund->status, ['failed', 'rejected']))
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-medium tracking-tight text-red-800"
                        >
                            <span class="inline-block size-1.5 rounded-full bg-red-800"></span>
                            {{ $refund->status === 'failed' ? 'Gagal' : 'Ditolak' }}
                        </span>
                    @endif
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Diajukan Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($refund->created_at) }}
                </dd>
            </div>

            @if ($refund->status === 'approved' && $refund->approved_at)
                <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Disetujui Pada</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ formatTimestamp($refund->approved_at) }}
                    </dd>
                </div>
            @elseif ($refund->status === 'succeeded' && $refund->succeeded_at)
                <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Berhasil Pada</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                        {{ formatTimestamp($refund->succeeded_at) }}
                    </dd>
                </div>
            @endif

            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Terakhir Diubah Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($refund->updated_at) }}
                </dd>
            </div>
        </dl>
        <div class="flex flex-col items-center gap-4 md:flex-row md:justify-end">
            <x-common.button
                :href="route('admin.refunds.index')"
                variant="secondary"
                class="w-full md:w-fit"
                wire:navigate
            >
                Kembali
            </x-common.button>
        </div>
    </section>
@endsection
