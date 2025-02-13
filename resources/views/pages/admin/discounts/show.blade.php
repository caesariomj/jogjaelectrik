@extends('layouts.admin')

@section('title', 'Detail Diskon ' . ucwords($discount->name))

@section('content')
    <section>
        <header class="mb-4 flex items-start">
            <x-common.button
                :href="route('admin.discounts.index')"
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
            <h1 class="leading-none text-black">Detail Diskon &mdash; {{ ucwords($discount->name) }}</h1>
        </header>
        <dl class="mb-8 grid grid-cols-1">
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nama Diskon</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ ucwords($discount->name) }}</dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Kode Diskon</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $discount->code }}</dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Jenis Diskon</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $discount->type === 'fixed' ? 'Nominal' : 'Persentase' }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nilai Potongan Diskon</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    @if ($discount->type === 'fixed')
                        Rp
                    @endif

                    {{ formatPrice($discount->value) }}

                    @if ($discount->type === 'percentage')
                        %
                        @if ($discount->max_discount_amount)
                            <span class="ms-2 text-sm text-black/70">
                                (Maksimal potongan harga: Rp {{ formatPrice($discount->max_discount_amount) }})
                            </span>
                        @endif
                    @endif
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Minimum Pembelian</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    Rp {{ formatPrice($discount->minimum_purchase) }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Deskripsi Diskon</dt>
                <dd class="-mt-6 w-full whitespace-pre-line font-medium tracking-tight text-black md:w-2/3">
                    {{ $discount->description ?? 'Tidak ada deskripsi.' }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Periode Diskon</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    @if ($discount->start_date && $discount->end_date)
                        {{ formatDate($discount->start_date) . ' - ' . formatDate($discount->end_date) }}
                    @else
                        Periode tidak ditentukan.
                    @endif
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Status Diskon</dt>
                <dd class="w-full md:w-2/3">
                    @if ($discount->is_active && (! $discount->end_date || $discount->end_date >= now()->toDateString()))
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                        >
                            <span class="inline-block size-1 rounded-full bg-teal-800"></span>
                            Aktif
                        </span>
                    @elseif ($discount->end_date && $discount->end_date < now()->toDateString())
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-medium tracking-tight text-red-800"
                        >
                            <span class="inline-block size-1 rounded-full bg-red-800"></span>
                            Kadaluarsa
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium tracking-tight text-yellow-800"
                        >
                            <span class="inline-block size-1 rounded-full bg-yellow-800"></span>
                            Non-Aktif
                        </span>
                    @endif
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Penggunaan Diskon</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $discount->used_count }} -
                    {{ $discount->usage_limit ?? 'Tidak ada batasan penggunaan.' }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Ditambahkan Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($discount->created_at) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Terakhir Diubah Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($discount->updated_at) }}
                </dd>
            </div>
        </dl>
        <div class="flex flex-col items-center gap-4 md:flex-row md:justify-end">
            <x-common.button
                :href="route('admin.discounts.index')"
                variant="secondary"
                class="w-full md:w-fit"
                wire:navigate
            >
                Kembali
            </x-common.button>
        </div>
    </section>
@endsection
