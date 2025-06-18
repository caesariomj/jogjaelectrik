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
                        <dt class="w-full tracking-tight text-black/70 md:w-1/3">Transaksi Dibuat Pada</dt>
                        <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                            {{ formatTimestamp($payment->created_at) }}
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
                <section>
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
                                        'bg-red-100 text-red-800' => $payment->refund->status === 'failed',
                                    ])
                                    role="status"
                                >
                                    <span
                                        @class([
                                            'inline-block size-1.5 rounded-full',
                                            'bg-yellow-800' => $payment->refund->status === 'pending',
                                            'bg-teal-800' => $payment->refund->status === 'succeeded',
                                            'bg-red-800' => $payment->refund->status === 'failed',
                                        ])
                                    ></span>
                                    @if ($payment->refund->status === 'pending')
                                        Menunggu Diproses
                                    @elseif ($payment->refund->status === 'succeeded')
                                        Berhasil
                                    @elseif ($payment->refund->status === 'failed')
                                        Gagal
                                    @endif
                                </span>
                            </dd>
                        </div>

                        @if ($payment->refund->status === 'failed')
                            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Pesan Gagal</dt>
                                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                                    {{ $payment->refund->rejection_reason ?? 'Permintaan refund gagal. Silakan hubungi kami untuk bantuan lebih lanjut.' }}
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
