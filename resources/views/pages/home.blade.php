@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <section x-data="{ shown: false }" x-intersect="shown = true" class="min-h-[50svh] md:min-h-[75svh]">
        <x-common.banner-carousel :slides="$bannerSlides" autoplayInterval="6000" />
    </section>
    <section
        class="container mx-auto grid h-full max-w-md grid-cols-1 gap-6 p-6 md:max-w-[96rem] md:grid-cols-2 md:gap-12 md:p-12"
    >
        @foreach ($primaryCategories as $category)
            <article class="group relative h-96 w-full overflow-hidden rounded-xl shadow-xl md:h-[40rem]">
                <a
                    href="{{ route('products.category', ['category' => $category->slug]) }}"
                    class="relative block h-full w-full"
                    wire:navigate
                >
                    <div class="absolute inset-0 z-[1] bg-gradient-to-t from-black to-transparent"></div>
                    <img
                        src="https://penguinui.s3.amazonaws.com/component-assets/carousel/default-slide-1.webp"
                        alt="Kategori {{ $category->name }}"
                        class="h-full w-full object-cover"
                        loading="lazy"
                    />
                    <div class="absolute bottom-0 start-0 z-[2] flex flex-col items-start gap-4 p-8">
                        <h2 class="w-full text-pretty text-white md:w-2/3">
                            {{ ucwords($category->name) }}
                        </h2>
                        <p class="mb-4 w-full text-base tracking-tight text-white/80 md:w-2/3">
                            @if ($loop->first)
                                Temukan berbagai pilihan {{ $category->name }} yang siap melengkapi kebutuhan Anda
                                dengan kualitas terbaik.
                            @else
                                Jelajahi koleksi {{ $category->name }} dengan pilihan terbaik untuk memenuhi kebutuhan
                                Anda.
                            @endif
                        </p>
                        <x-common.button variant="secondary">
                            Lihat {{ ucwords($category->name) }}
                            <svg
                                class="size-5 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M18 8L22 12L18 16" />
                                <path d="M2 12H22" />
                            </svg>
                        </x-common.button>
                    </div>
                </a>
            </article>
        @endforeach
    </section>
    <x-common.product-slider-section :products="$bestSellingProducts">
        <x-slot name="header">
            <h2 class="text-pretty text-black">Paling Banyak Dibeli</h2>
            <x-common.button variant="secondary" :href="route('products').'?sort=terlaris'" wire:navigate>
                Selengkapnya
                <svg
                    class="size-5 shrink-0"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M18 8L22 12L18 16" />
                    <path d="M2 12H22" />
                </svg>
            </x-common.button>
        </x-slot>
    </x-common.product-slider-section>
    <section
        class="container mx-auto flex max-w-md flex-col items-center justify-between gap-12 p-6 md:max-w-[96rem] md:p-12 lg:flex-row"
    >
        @if ($activeDiscount)
            <livewire:discount-card :discount="$activeDiscount" />
        @endif

        <figure
            @class([
                'group relative h-96 w-full overflow-hidden rounded-xl shadow-xl lg:h-[36rem]',
                'lg:w-2/3' => $activeDiscount,
                'lg:w-3/3' => ! $activeDiscount,
            ])
        >
            <a href="{{ route('products') }}" wire:navigate>
                <div class="absolute inset-0 z-[1] bg-gradient-to-t from-black to-transparent"></div>
                <img
                    src="https://penguinui.s3.amazonaws.com/component-assets/carousel/default-slide-2.webp"
                    alt="CTA"
                    class="h-full w-full object-cover"
                    loading="lazy"
                />
                <figcaption class="absolute bottom-0 start-0 z-[2] flex flex-col items-start gap-4 p-8">
                    <h2 class="w-full leading-none text-white md:w-2/3">Temukan Penawaran Terbaik Hari Ini</h2>
                    <p class="mb-4 w-full text-base tracking-tight text-white/80 md:w-2/3">
                        Jelajahi berbagai produk berkualitas yang dirancang untuk melengkapi kebutuhan rumah Anda. Dari
                        produk elektronik hingga peralatan rumah tangga, temukan solusi sempurna untuk kenyamanan serta
                        efisiensi rumah Anda.
                    </p>
                    <x-common.button variant="secondary">
                        Lihat Produk
                        <svg
                            class="size-5 shrink-0"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M18 8L22 12L18 16" />
                            <path d="M2 12H22" />
                        </svg>
                    </x-common.button>
                </figcaption>
            </a>
        </figure>
    </section>
    <x-common.product-slider-section class="pb-6 md:pb-9" :products="$latestProducts" context="latest">
        <x-slot name="header">
            <h2 class="text-pretty text-black">Produk Terbaru</h2>
            <x-common.button variant="secondary" :href="route('products').'?sort=terbaru'" wire:navigate>
                Selengkapnya
                <svg
                    class="size-5 shrink-0"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M18 8L22 12L18 16" />
                    <path d="M2 12H22" />
                </svg>
            </x-common.button>
        </x-slot>
    </x-common.product-slider-section>
    <section class="border-t border-t-neutral-300">
        <div
            class="container mx-auto grid h-auto max-w-md grid-cols-1 items-start gap-6 p-6 md:max-w-[96rem] md:p-12 lg:grid-cols-3"
        >
            <div class="flex flex-col items-start justify-center gap-6">
                <span class="flex size-16 items-center justify-center rounded-full bg-primary-50 p-2 md:size-24">
                    <svg
                        class="size-8 text-primary md:size-10"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                    >
                        <path d="M12 7.5a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                        <path
                            fill-rule="evenodd"
                            d="M1.5 4.875C1.5 3.839 2.34 3 3.375 3h17.25c1.035 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 14.625v-9.75ZM8.25 9.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM18.75 9a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V9.75a.75.75 0 0 0-.75-.75h-.008ZM4.5 9.75A.75.75 0 0 1 5.25 9h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V9.75Z"
                            clip-rule="evenodd"
                        />
                        <path
                            d="M2.25 18a.75.75 0 0 0 0 1.5c5.4 0 10.63.722 15.6 2.075 1.19.324 2.4-.558 2.4-1.82V18.75a.75.75 0 0 0-.75-.75H2.25Z"
                        />
                    </svg>
                </span>
                <div class="flex flex-col gap-3">
                    <h2 class="text-pretty text-black">Harga Kompetitif dengan Penawaran Eksklusif</h2>
                    <p class="text-base font-normal tracking-tight text-black/70">
                        Kami menawarkan harga terbaik untuk elektronik rumah tangga berkualitas tinggi, dengan penawaran
                        eksklusif dan promosi yang membuat berbelanja bersama kami semakin bermanfaat.
                    </p>
                </div>
            </div>
            <div class="flex flex-col items-start justify-center gap-6">
                <span class="flex size-16 items-center justify-center rounded-full bg-primary-50 p-2 md:size-24">
                    <svg
                        class="size-8 text-primary md:size-10"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 256 256"
                        fill="currentColor"
                    >
                        <path
                            d="M232,128v80a40,40,0,0,1-40,40H136a8,8,0,0,1,0-16h56a24,24,0,0,0,24-24H192a24,24,0,0,1-24-24V144a24,24,0,0,1,24-24h23.65A88,88,0,0,0,66,65.54,87.29,87.29,0,0,0,40.36,120H64a24,24,0,0,1,24,24v40a24,24,0,0,1-24,24H48a24,24,0,0,1-24-24V128A104.11,104.11,0,0,1,201.89,54.66,103.41,103.41,0,0,1,232,128Z"
                        />
                    </svg>
                </span>
                <div class="flex flex-col gap-3">
                    <h2 class="text-pretty text-black">Dukungan Pelanggan yang Responsif</h2>
                    <p class="text-base font-normal tracking-tight text-black/70">
                        Tim dukungan pelanggan kami yang berdedikasi siap membantu kapan pun Anda membutuhkannya. Baik
                        Anda memiliki pertanyaan atau memerlukan bantuan, kami berkomitmen untuk memberikan solusi yang
                        cepat dan efektif.
                    </p>
                </div>
            </div>
            <div class="flex flex-col items-start justify-center gap-6">
                <span class="flex size-16 items-center justify-center rounded-full bg-primary-50 p-2 md:size-24">
                    <svg
                        class="size-8 text-primary md:size-10"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 256 256"
                        fill="currentColor"
                    >
                        <path
                            d="M243.31,90.91l-128.4,128.4a16,16,0,0,1-22.62,0l-71.62-72a16,16,0,0,1,0-22.61l20-20a16,16,0,0,1,22.58,0L104,144.22l96.76-95.57a16,16,0,0,1,22.59,0l19.95,19.54A16,16,0,0,1,243.31,90.91Z"
                        />
                    </svg>
                </span>
                <div class="flex flex-col gap-3">
                    <h2 class="text-pretty text-black">Produk Berkualitas Tinggi</h2>
                    <p class="text-base font-normal tracking-tight text-black/70">
                        Pilihan peralatan elektronik rumah tangga kami dipilih dengan cermat untuk keandalan dan
                        kinerja, memastikan Anda menerima produk yang dibuat agar tahan lama dan meningkatkan pengalaman
                        rumah Anda.
                    </p>
                </div>
            </div>
        </div>
    </section>
    {{--
        <section class="h-96 bg-blue-50 px-4 pt-6 md:px-6 md:pt-12">
        Sticky Bottom Bar (Mobile Only, Quick links for Home, Categories, Cart, and Account improve mobile navigation)
        </section>
    --}}
@endsection
