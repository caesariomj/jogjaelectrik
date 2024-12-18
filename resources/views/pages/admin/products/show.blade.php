@extends('layouts.admin')

@section('title', 'Detail Produk ' . ucwords($product->name))

@section('content')
    <section>
        <header class="mb-4 flex items-start">
            <x-common.button
                :href="route('admin.products.index')"
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
            <h1 class="leading-none text-black">Detail Produk &mdash; {{ ucwords($product->name) }}</h1>
        </header>
        <ul class="mb-2 grid grid-cols-2 gap-4 md:grid-cols-5">
            @php
                $thumbnailImage = $product
                    ->images()
                    ->thumbnail()
                    ->first();
                $nonThumbnailImages = $product
                    ->images()
                    ->nonThumbnail()
                    ->get();
            @endphp

            <li>
                <figure class="relative h-auto w-full">
                    <img
                        class="mb-2 aspect-square h-full w-full rounded-md border border-neutral-300 object-cover"
                        src="{{ asset('storage/uploads/product-images/' . $thumbnailImage->file_name) }}"
                        alt="Gambar produk {{ $product->name }}"
                        loading="lazy"
                    />
                    <figcaption class="text-center text-sm font-medium tracking-tight text-black">
                        Gambar produk {{ $product->name . ' - 1' }}
                    </figcaption>
                    <div
                        class="absolute -start-3 -top-3 flex items-center justify-center rounded-full bg-primary px-2 py-1"
                    >
                        <span class="text-xs font-medium tracking-tight text-white">Gambar Utama</span>
                    </div>
                </figure>
            </li>

            @foreach ($nonThumbnailImages as $image)
                <li>
                    <figure class="h-auto w-full">
                        <img
                            class="mb-2 aspect-square h-full w-full rounded-md border border-neutral-300 object-cover"
                            src="{{ asset('storage/uploads/product-images/' . $image->file_name) }}"
                            alt="Gambar produk {{ $product->name }}"
                            loading="lazy"
                        />
                        <figcaption class="text-center text-sm font-medium tracking-tight text-black">
                            Gambar produk {{ $product->name . ' - ' . $loop->index + 2 }}
                        </figcaption>
                    </figure>
                </li>
            @endforeach
        </ul>
        <dl class="grid grid-cols-1">
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nama Produk</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ ucwords($product->name) }}</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Kategori Terkait</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->subcategory ? ucwords($product->subcategory->category->name) : 'Produk belum terkait dengan kategori' }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Subkategori Terkait</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->subcategory ? ucwords($product->subcategory->name) : 'Produk belum terkait dengan subkategori' }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">SKU Utama Produk</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->main_sku }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Harga Produk</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    @if ($product->variants->count() > 1)
                        <span class="me-1">Mulai dari</span>
                    @endif

                    @if ($product->base_price_discount)
                        Rp {{ formatPrice($product->base_price_discount) }}
                        <del class="ms-1 text-xs tracking-tight text-black/50">
                            Rp {{ formatPrice($product->base_price) }}
                        </del>
                    @else
                        Rp {{ formatPrice($product->base_price) }}
                    @endif
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Stok Produk</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $product->totalStock() }}</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Produk Terjual</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->total_sold ? formatPrice($product->total_sold) : 0 }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Penilaian Produk</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">0</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Status Produk</dt>
                <dd class="w-full md:w-2/3">
                    @if ($product->is_active)
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                        >
                            <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                            Aktif
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-x-1.5 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium tracking-tight text-yellow-800"
                        >
                            <span class="inline-block size-1.5 rounded-full bg-yellow-800"></span>
                            Non-Aktif
                        </span>
                    @endif
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Informasi Garansi Produk</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->warranty }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Bahan Material Produk</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->material }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Dimensi Produk (panjang x lebar x tinggi)</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->dimension }} (dalam satuan centimeter)
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Apa Yang Ada Di dalam Paket</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->package }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Berat Produk</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $product->weight }} gram</dd>
            </div>
            @if ($product->power && $product->voltage)
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Daya Listrik</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $product->power }} W</dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full tracking-tight text-black/70 md:w-1/3">Tegangan</dt>
                    <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $product->voltage }} V</dd>
                </div>
            @endif

            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Ditambahkan Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($product->created_at) }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Terakhir Diubah Pada</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ formatTimestamp($product->updated_at) }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Deskripsi Produk</dt>
                <dd class="-mt-6 w-full whitespace-pre-line font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->description }}
                </dd>
            </div>
            @if ($product->variants->count() > 1)
                <div class="flex flex-col items-center gap-2 border-b border-neutral-300 py-2">
                    <dt class="w-full tracking-tight text-black/70">Tabel Variasi Produk</dt>
                    <dd class="w-full overflow-x-auto rounded-lg border border-neutral-300">
                        <table class="min-w-full border-collapse border text-left">
                            <thead class="bg-neutral-100">
                                <tr>
                                    <th class="w-36 border px-4 py-2 text-sm font-medium tracking-tight text-black/70">
                                        Nama Variasi
                                    </th>
                                    <th class="w-40 border px-4 py-2 text-sm font-medium tracking-tight text-black/70">
                                        Varian
                                    </th>
                                    <th class="w-56 border px-4 py-2 text-sm font-medium tracking-tight text-black/70">
                                        Harga
                                    </th>
                                    <th class="w-28 border px-4 py-2 text-sm font-medium tracking-tight text-black/70">
                                        Stok
                                    </th>
                                    <th class="w-28 border px-4 py-2 text-sm font-medium tracking-tight text-black/70">
                                        Total Terjual
                                    </th>
                                    <th class="w-56 border px-4 py-2 text-sm font-medium tracking-tight text-black/70">
                                        Kode Varian
                                    </th>
                                    <th class="w-28 border px-4 py-2 text-sm font-medium tracking-tight text-black/70">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($product->variants as $variant)
                                    <tr>
                                        @if ($loop->first)
                                            <td
                                                class="w-36 whitespace-nowrap border px-4 py-2 tracking-tight text-black"
                                                rowspan="{{ $product->variants->count() }}"
                                            >
                                                {{ ucwords($variant->combinations->first()->variationVariant->variation->name) }}
                                            </td>
                                        @endif

                                        <td class="w-40 whitespace-nowrap border px-4 py-2 tracking-tight text-black">
                                            {{ ucwords($variant->combinations->first()->variationVariant->name) }}
                                        </td>
                                        <td class="w-56 whitespace-nowrap border px-4 py-2 tracking-tight text-black">
                                            @if ($variant->price_discount)
                                                Rp {{ formatPrice($variant->price_discount) }}
                                                <del class="ms-2 text-xs text-black/50">
                                                    Rp {{ formatPrice($variant->price) }}
                                                </del>
                                            @else
                                                Rp {{ formatPrice($variant->price) }}
                                            @endif
                                        </td>
                                        <td class="w-28 whitespace-nowrap border px-4 py-2 tracking-tight text-black">
                                            {{ $variant->stock }}
                                        </td>
                                        <td class="w-28 whitespace-nowrap border px-4 py-2 tracking-tight text-black">
                                            0
                                        </td>
                                        <td class="w-56 whitespace-nowrap border px-4 py-2 tracking-tight text-black">
                                            {{ $variant->variant_sku }}
                                        </td>
                                        <td class="w-28 whitespace-nowrap border px-4 py-2">
                                            @if ($variant->is_active)
                                                <span
                                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-medium tracking-tight text-teal-800"
                                                >
                                                    <span class="inline-block size-1.5 rounded-full bg-teal-800"></span>
                                                    Aktif
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium tracking-tight text-yellow-800"
                                                >
                                                    <span
                                                        class="inline-block size-1.5 rounded-full bg-yellow-800"
                                                    ></span>
                                                    Non-Aktif
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </dd>
                </div>
            @endif
        </dl>
    </section>
@endsection
