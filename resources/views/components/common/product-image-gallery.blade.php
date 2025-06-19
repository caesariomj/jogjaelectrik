@props([
    'images',
    'productName',
])

@php
    $thumbnail = $images
        ->filter(function ($image) {
            return $image->is_thumbnail;
        })
        ->first();

    $images = $images->filter(function ($image) {
        return ! $image->is_thumbnail;
    });
@endphp

@if ($thumbnail && $images)
    <section
        class="relative flex w-full flex-col-reverse gap-2 overflow-y-hidden lg:sticky lg:top-[4.5rem] lg:h-[38rem] lg:w-1/2 lg:flex-row lg:gap-4"
        x-data="productImageGallery(
                    '{{ asset('storage/uploads/product-images/' . $thumbnail->file_name) }}',
                )"
    >
        <div class="relative h-full">
            <ul
                class="flex h-full flex-row flex-wrap gap-2 transition-transform lg:flex-col lg:flex-nowrap"
                :style="`transform: translateY(-${scrollPosition}px)`"
                aria-label="List gambar produk {{ $productName }}"
                x-ref="slider"
            >
                <li>
                    <button
                        class="group size-20 overflow-hidden rounded-xl border transition-colors"
                        :class="{
                        'border-primary': selectedImage === '{{ asset('storage/uploads/product-images/' . $thumbnail->file_name) }}',
                        'border-neutral-300 hover:border-primary': selectedImage !== '{{ asset('storage/uploads/product-images/' . $thumbnail->file_name) }}'
                    }"
                        aria-label="Tampilkan gambar utama produk {{ $productName }} - 1"
                        x-on:click="
                            selectImage(
                                '{{ asset('storage/uploads/product-images/' . $thumbnail->file_name) }}',
                            )
                        "
                    >
                        <img
                            src="{{ asset('storage/uploads/product-images/' . $thumbnail->file_name) }}"
                            class="h-auto w-full object-cover transition-colors"
                            :class="{
                            'brightness-[0.9]': selectedImage === '{{ asset('storage/uploads/product-images/' . $thumbnail->file_name) }}',
                            'brightness-100 group-hover:brightness-[0.9]': selectedImage !== '{{ asset('storage/uploads/product-images/' . $thumbnail->file_name) }}'
                        }"
                            alt="Gambar produk {{ $productName }} - 1"
                            loading="lazy"
                        />
                    </button>
                </li>
                @if ($images->count() > 0)
                    @foreach ($images as $image)
                        <li>
                            <button
                                class="group size-20 overflow-hidden rounded-xl border transition-colors"
                                :class="{
                                'border-primary': selectedImage === '{{ asset('storage/uploads/product-images/' . $image->file_name) }}',
                                'border-neutral-300 hover:border-primary': selectedImage !== '{{ asset('storage/uploads/product-images/' . $image->file_name) }}'
                            }"
                                aria-label="Tampilkan gambar utama produk {{ $productName . ' - ' . $loop->index + 2 }}"
                                x-on:click="
                                    selectImage(
                                        '{{ asset('storage/uploads/product-images/' . $image->file_name) }}',
                                    )
                                "
                            >
                                <img
                                    src="{{ asset('storage/uploads/product-images/' . $image->file_name) }}"
                                    class="h-auto w-full object-cover transition-colors"
                                    :class="{
                                    'brightness-[0.9]': selectedImage === '{{ asset('storage/uploads/product-images/' . $image->file_name) }}',
                                    'brightness-100 group-hover:brightness-[0.9]': selectedImage !== '{{ asset('storage/uploads/product-images/' . $image->file_name) }}'
                                }"
                                    alt="Gambar produk {{ $productName . ' - ' . $loop->index + 2 }}"
                                    loading="lazy"
                                />
                            </button>
                        </li>
                    @endforeach
                @endif
            </ul>
            @if ($images->count() > 6)
                <button
                    class="absolute inset-x-0 top-0 hidden justify-center bg-gradient-to-t from-transparent to-white to-50% px-2 py-8 text-black transition-colors hover:text-primary lg:flex"
                    aria-label="Geser galeri gambar produk ke atas"
                    x-on:click="scrollUp"
                    x-show="!isAtTop"
                    x-transition.opacity
                    x-cloak
                >
                    <svg
                        class="size-6 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m18 15-6-6-6 6" />
                    </svg>
                </button>
                <button
                    class="absolute inset-x-0 bottom-0 hidden justify-center bg-gradient-to-b from-transparent to-white to-50% px-2 py-8 text-black transition-colors hover:text-primary lg:flex"
                    aria-label="Geser galeri gambar produk ke bawah"
                    x-on:click="scrollDown"
                    x-show="!isAtBottom"
                    x-transition.opacity
                    x-cloak
                >
                    <svg
                        class="size-6 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>
            @endif
        </div>
        <figure class="relative mb-4 flex-grow overflow-hidden rounded-xl border border-neutral-300 lg:mb-0">
            <img
                :src="selectedImage"
                class="h-full w-full object-cover"
                alt="Gambar utama produk {{ $productName }}"
                loading="lazy"
            />
            <figcaption class="sr-only">Gambar utama produk</figcaption>
        </figure>
    </section>
@else
    <div
        class="relative flex h-[32rem] w-full items-center justify-center gap-2 overflow-y-hidden rounded-xl border border-neutral-300 bg-neutral-100 lg:sticky lg:top-[4.5rem] lg:h-[38rem] lg:w-1/2 lg:gap-4"
    >
        <x-common.application-logo class="block h-16 w-auto fill-current text-primary saturate-0 md:h-20" />
    </div>
@endif
