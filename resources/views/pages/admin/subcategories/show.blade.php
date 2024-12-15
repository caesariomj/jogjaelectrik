@extends('layouts.admin')

@section('title', 'Detail Subkategori ' . ucwords($subcategory->name))

@section('content')
    <section>
        <header class="mb-4 flex items-start">
            <x-common.button
                :href="route('admin.subcategories.index')"
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
            <h1 class="leading-none text-black">Detail Subkategori &mdash; {{ ucwords($subcategory->name) }}</h1>
        </header>
        <dl class="grid grid-cols-1">
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nama Subkategori</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ ucwords($subcategory->name) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Kategori Terkait</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ ucwords($subcategory->category->name) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Produk Terkait</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatPrice($subcategory->products_count) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Ditambahkan Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($subcategory->created_at) }}
                </dd>
            </div>
            <div class="flex flex-col items-center gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Terakhir Diubah Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($subcategory->updated_at) }}
                </dd>
            </div>
        </dl>
    </section>
@endsection
