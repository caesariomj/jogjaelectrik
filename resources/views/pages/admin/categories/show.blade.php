@extends('layouts.admin')

@section('title', 'Detail Kategori ' . ucwords($category->name))

@section('content')
    <section>
        <header class="mb-4 flex items-start">
            <x-common.button
                :href="route('admin.categories.index')"
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
            <h1 class="leading-none text-black">Detail Kategori &mdash; {{ ucwords($category->name) }}</h1>
        </header>
        <dl class="mb-8 grid grid-cols-1">
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nama Kategori</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ ucwords($category->name) }}</dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="inline-flex w-full items-center gap-x-2 tracking-tight text-black/70 md:w-1/3">
                    Kategori Utama
                    <x-common.tooltip
                        id="primary-category-information"
                        class="z-[3] w-72"
                        text="Kategori utama akan ditampilkan pada halaman utama. Anda hanya dapat menetapkan maksimal 2 kategori sebagai kategori utama."
                    />
                </dt>
                <dd class="w-full md:w-2/3">
                    @if ($category->is_primary)
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                        >
                            <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                            Ya
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium tracking-tight text-neutral-800"
                        >
                            <span class="inline-block size-1.5 rounded-full bg-neutral-800"></span>
                            Tidak
                        </span>
                    @endif
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Subkategori Terkait</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatPrice($category->total_subcategories) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Produk Terkait</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatPrice($category->total_products) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Ditambahkan Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($category->created_at) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Terakhir Diubah Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($category->updated_at) }}
                </dd>
            </div>
        </dl>
        <div class="flex flex-col items-center gap-4 md:flex-row md:justify-end">
            <x-common.button
                :href="route('admin.categories.index')"
                variant="secondary"
                class="w-full md:w-fit"
                wire:navigate
            >
                Kembali
            </x-common.button>
        </div>
    </section>
@endsection
