@extends('layouts.admin')

@section('title', 'Detail Produk ' . ucwords($product->name))

@section('content')
    <section>
        <h1 class="mb-4 text-black">Detail Produk &mdash; {{ ucwords($product->name) }}</h1>
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
                        src="{{ asset('uploads/product-images/' . $thumbnailImage->file_name) }}"
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
                            src="{{ asset('uploads/product-images/' . $image->file_name) }}"
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
                <dt class="w-full text-black/70 md:w-1/3">Nama Produk</dt>
                <dd class="w-full font-medium text-black md:w-2/3">{{ ucwords($product->name) }}</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Kategori Terkait</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ ucwords($product->subcategory->category->name) }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Subkategori Terkait</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ ucwords($product->subcategory->name) }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">SKU Utama Produk</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ $product->main_sku }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Harga Produk</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    @if ($product->variants->count() > 1)
                        <span class="me-1">Mulai dari</span>
                    @endif

                    @if ($product->base_price_discount)
                        Rp {{ formatPrice($product->base_price_discount) }}
                        <del class="ms-1 text-xs text-black/50">Rp {{ formatPrice($product->base_price) }}</del>
                    @else
                        Rp {{ formatPrice($product->base_price) }}
                    @endif
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Total Stok Produk</dt>
                <dd class="w-full font-medium text-black md:w-2/3">{{ $product->totalStock() }}</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Total Produk Terjual</dt>
                <dd class="w-full font-medium text-black md:w-2/3">0</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Total Penilaian Produk</dt>
                <dd class="w-full font-medium text-black md:w-2/3">0</dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Status Produk</dt>
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
                <dt class="w-full text-black/70 md:w-1/3">Informasi Garansi Produk</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ $product->warranty }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Bahan Material Produk</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ $product->material }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Dimensi Produk</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ $product->dimension }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Apa Yang Ada Di dalam Paket</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ $product->package }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Berat Produk</dt>
                <dd class="w-full font-medium text-black md:w-2/3">{{ $product->weight }} gram</dd>
            </div>
            @if ($product->power && $product->voltage)
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full text-black/70 md:w-1/3">Daya Listrik</dt>
                    <dd class="w-full font-medium text-black md:w-2/3">{{ $product->power }} W</dd>
                </div>
                <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                    <dt class="w-full text-black/70 md:w-1/3">Tegangan</dt>
                    <dd class="w-full font-medium text-black md:w-2/3">{{ $product->voltage }} V</dd>
                </div>
            @endif

            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Ditambahkan Pada</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ formatTimestamp($product->created_at) }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Terakhir Diubah Pada</dt>
                <dd class="w-full font-medium text-black md:w-2/3">
                    {{ formatTimestamp($product->updated_at) }}
                </dd>
            </div>
            <div class="flex flex-col items-start gap-1 border-b border-neutral-300 py-2 md:flex-row">
                <dt class="w-full text-black/70 md:w-1/3">Deskripsi Produk</dt>
                <dd class="w-full whitespace-pre-line font-medium text-black md:w-2/3">{{ $product->description }}</dd>
            </div>
            @if ($product->variants->count() > 1)
                <div class="flex flex-col items-center gap-2 border-b border-neutral-300 py-2">
                    <dt class="w-full text-black/70">Tabel Variasi Produk</dt>
                    <dd class="w-full overflow-x-auto rounded-lg border border-neutral-300">
                        <table class="min-w-full border-collapse border text-left">
                            <thead class="bg-neutral-100">
                                <tr>
                                    <th class="w-36 border px-4 py-2 text-sm font-medium text-black/70">
                                        Nama Variasi
                                    </th>
                                    <th class="w-40 border px-4 py-2 text-sm font-medium text-black/70">Varian</th>
                                    <th class="w-56 border px-4 py-2 text-sm font-medium text-black/70">Harga</th>
                                    <th class="w-28 border px-4 py-2 text-sm font-medium text-black/70">Stok</th>
                                    <th class="w-28 border px-4 py-2 text-sm font-medium text-black/70">
                                        Total Terjual
                                    </th>
                                    <th class="w-56 border px-4 py-2 text-sm font-medium text-black/70">Kode Varian</th>
                                    <th class="w-28 border px-4 py-2 text-sm font-medium text-black/70">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($product->variants as $variant)
                                    <tr>
                                        @if ($loop->first)
                                            <td
                                                class="w-36 whitespace-nowrap border px-4 py-2"
                                                rowspan="{{ $product->variants->count() }}"
                                            >
                                                {{ ucwords($variant->combinations->first()->variationVariant->variation->name) }}
                                            </td>
                                        @endif

                                        <td class="w-40 whitespace-nowrap border px-4 py-2">
                                            {{ ucwords($variant->combinations->first()->variationVariant->name) }}
                                        </td>
                                        <td class="w-56 whitespace-nowrap border px-4 py-2">
                                            @if ($variant->price_discount)
                                                Rp {{ formatPrice($variant->price_discount) }}
                                                <del class="ms-2 text-xs text-black/50">
                                                    Rp {{ formatPrice($variant->price) }}
                                                </del>
                                            @else
                                                Rp {{ formatPrice($variant->price) }}
                                            @endif
                                        </td>
                                        <td class="w-28 whitespace-nowrap border px-4 py-2">{{ $variant->stock }}</td>
                                        <td class="w-28 whitespace-nowrap border px-4 py-2">0</td>
                                        <td class="w-56 whitespace-nowrap border px-4 py-2">
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
        <div class="mt-10 flex flex-col items-center gap-4 md:flex-row md:justify-end">
            @can('edit products')
                <x-common.button
                    variant="secondary"
                    :href="route('admin.products.edit', ['slug' => $product->slug])"
                    wire:navigate
                >
                    <svg
                        class="size-4"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path
                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"
                        />
                        <path d="m15 5 4 4" />
                    </svg>
                    Ubah
                </x-common.button>
            @endcan

            @can('archive products')
                <x-common.button
                    variant="danger"
                    x-on:click.prevent="$dispatch('open-modal', 'confirm-product-archiving-{{ $product->id }}')"
                >
                    <svg
                        class="size-4"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect width="20" height="5" x="2" y="3" rx="1" />
                        <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                        <path d="M10 12h4" />
                    </svg>
                    Arsip
                </x-common.button>
                @push('overlays')
                    <x-common.modal
                        name="confirm-product-archiving-{{ $product->id }}"
                        :show="$errors->isNotEmpty()"
                        focusable
                    >
                        <form
                            action="{{ route('admin.products.destroy', ['product' => $product]) }}"
                            method="POST"
                            class="flex flex-col items-center p-6"
                        >
                            @csrf
                            @method('DELETE')
                            <div class="mb-4 rounded-full bg-red-100 p-4" aria-hidden="true">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    class="size-16 text-red-500"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <h2 class="mb-2 text-center text-black">Arsip Produk {{ ucwords($product->name) }}</h2>
                            <p class="mb-8 text-center text-base font-medium tracking-tight text-black/70">
                                Apakah anda yakin ingin mengarsip produk
                                <strong class="text-black">"{{ strtolower($product->name) }}"</strong>
                                ini ? Produk yang diarsip tidak akan dapat dilihat maupun dibeli oleh pembeli. Anda
                                dapat melihat produk yang di arsip pada menu
                                <a href="{{ route('admin.archived-products.index') }}" class="underline" wire:navigate>
                                    arsip produk
                                </a>
                                .
                            </p>
                            <div class="flex justify-end gap-4">
                                <x-common.button variant="secondary" x-on:click="$dispatch('close')">
                                    Batal
                                </x-common.button>
                                <x-common.button type="submit" variant="danger">Arsip Produk</x-common.button>
                            </div>
                        </form>
                    </x-common.modal>
                @endpush
            @endcan
        </div>
    </section>
@endsection
