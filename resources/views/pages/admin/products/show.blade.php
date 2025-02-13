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
                $images = $product->images;

                $thumbnailImage = array_shift($images);

                $nonThumbnailImages = $images;
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
                            Gambar produk {{ $product->name . ' - ' . $loop->iteration + 1 }}
                        </figcaption>
                    </figure>
                </li>
            @endforeach
        </ul>
        <dl class="mb-8 grid grid-cols-1">
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Nama Produk</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ ucwords($product->name) }}</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Kategori Terkait</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->category ? ucwords($product->category->name) : 'Produk belum terkait dengan kategori' }}
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
                    @if ($product->variation)
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
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">{{ $product->total_stock }}</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Produk Terjual</dt>
                <dd class="w-full font-medium tracking-tight text-black md:w-2/3">
                    {{ $product->total_sold ? formatPrice($product->total_sold) : 0 }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full tracking-tight text-black/70 md:w-1/3">Total Penilaian Produk</dt>
                <dd class="inline-flex w-full items-center gap-x-2 font-medium tracking-tight text-black md:w-2/3">
                    <svg
                        class="size-4 text-yellow-500"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path
                            d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                        />
                    </svg>

                    {{ $product->average_rating }}
                </dd>
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
            @if ($product->variation)
                <div class="flex flex-col items-center gap-2 border-b border-neutral-300 py-2">
                    <dt class="w-full tracking-tight text-black/70">Tabel Variasi Produk</dt>
                    <dd class="w-full overflow-x-auto rounded-lg border border-neutral-300">
                        <table class="min-w-full border-collapse border text-left">
                            <thead class="bg-neutral-100">
                                <tr>
                                    <th
                                        class="min-w-36 border px-4 py-2 text-sm font-medium tracking-tight text-black/70"
                                    >
                                        Nama Variasi
                                    </th>
                                    <th
                                        class="min-w-40 border px-4 py-2 text-sm font-medium tracking-tight text-black/70"
                                    >
                                        Varian
                                    </th>
                                    <th
                                        class="min-w-56 border px-4 py-2 text-sm font-medium tracking-tight text-black/70"
                                    >
                                        Harga
                                    </th>
                                    <th
                                        class="min-w-16 border px-4 py-2 text-sm font-medium tracking-tight text-black/70"
                                        align="center"
                                    >
                                        Stok
                                    </th>
                                    <th
                                        class="min-w-28 border px-4 py-2 text-sm font-medium tracking-tight text-black/70"
                                        align="center"
                                    >
                                        Total Terjual
                                    </th>
                                    <th
                                        class="min-w-56 border px-4 py-2 text-sm font-medium tracking-tight text-black/70"
                                    >
                                        SKU Varian
                                    </th>
                                    <th
                                        class="min-w-28 border px-4 py-2 text-sm font-medium tracking-tight text-black/70"
                                        align="center"
                                    >
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($product->variation->variants as $variant)
                                    <tr>
                                        @if ($loop->first)
                                            <td
                                                class="whitespace-nowrap border px-4 py-2 tracking-tight text-black"
                                                rowspan="{{ count($product->variation->variants) }}"
                                            >
                                                {{ ucwords($product->variation->name) }}
                                            </td>
                                        @endif

                                        <td class="whitespace-nowrap border px-4 py-2 tracking-tight text-black">
                                            {{ ucwords($variant->name) }}
                                        </td>
                                        <td class="whitespace-nowrap border px-4 py-2 tracking-tight text-black">
                                            @if ($variant->price_discount)
                                                Rp {{ formatPrice($variant->price_discount) }}
                                                <del class="ms-2 text-xs text-black/50">
                                                    Rp {{ formatPrice($variant->price) }}
                                                </del>
                                            @else
                                                Rp {{ formatPrice($variant->price) }}
                                            @endif
                                        </td>
                                        <td
                                            class="whitespace-nowrap border px-4 py-2 tracking-tight text-black"
                                            align="center"
                                        >
                                            {{ $variant->stock }}
                                        </td>
                                        <td
                                            class="whitespace-nowrap border px-4 py-2 tracking-tight text-black"
                                            align="center"
                                        >
                                            {{ $variant->total_sold }}
                                        </td>
                                        <td class="whitespace-nowrap border px-4 py-2 tracking-tight text-black">
                                            {{ $variant->sku }}
                                        </td>
                                        <td class="whitespace-nowrap border px-4 py-2" align="center">
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
        <div class="flex flex-col items-center gap-4 md:flex-row md:justify-end">
            <x-common.button
                :href="route('admin.products.index')"
                variant="secondary"
                class="w-full md:w-fit"
                wire:navigate
            >
                Kembali
            </x-common.button>
        </div>
    </section>
@endsection
