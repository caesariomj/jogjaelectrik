@props([
    'products',
    'context' => null,
])

<section
    x-data="productSlider({ products: @js($products) })"
    {{ $attributes->merge(['class' => 'relative overflow-hidden']) }}
>
    <div
        class="container mx-auto flex max-w-md items-center justify-between gap-x-6 px-6 pb-6 pt-3 md:max-w-[96rem] md:px-12"
    >
        {{ $header }}
    </div>
    <div
        class="flex transition-transform"
        :style="'transform: ' + transformX()"
        aria-live="polite"
        x-on:touchstart="handleTouchStart($event)"
        x-on:touchmove="handleTouchMove($event)"
        x-on:touchend="handleTouchEnd()"
    >
        <template x-for="(product, index) in products" :key="index">
            <article class="w-1/2 flex-shrink-0 px-4 md:w-1/4">
                <div class="group relative mb-4 h-60 w-full overflow-hidden rounded-lg sm:h-80 md:h-96">
                    <a :href="product.link" wire:navigate>
                        <img
                            :src="product.thumbnail"
                            :alt="`Gambar produk ${product.name}`"
                            class="h-full w-full object-cover transition-transform group-hover:scale-105"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 z-[1] bg-gradient-to-t from-black to-transparent opacity-10"></div>
                        @if ($context === 'latest')
                            <div class="absolute end-0 top-0 z-[1] rounded-bl-lg rounded-tr-lg bg-primary px-4 py-0.5">
                                <span class="text-sm font-semibold leading-none tracking-tight text-white">Baru</span>
                            </div>
                        @endif
                    </a>
                </div>
                <div class="flex flex-row items-start gap-2">
                    <div class="flex-grow">
                        <a :href="product.link" wire:navigate>
                            <h3
                                class="mb-2 text-base font-semibold text-black transition-colors group-hover:text-primary sm:text-lg lg:text-xl"
                                x-text="product.name"
                            ></h3>
                        </a>
                        <template x-if="product.price_discount">
                            <p class="flex w-full flex-wrap items-center gap-2">
                                <data
                                    class="text-base font-semibold tracking-tighter text-black lg:text-lg"
                                    :value="product.price_discount"
                                    x-text="formatPrice(product.price_discount)"
                                ></data>
                                <del
                                    class="text-sm tracking-tighter text-black/70"
                                    x-text="formatPrice(product.price)"
                                ></del>
                            </p>
                        </template>
                        <template x-if="!product.price_discount">
                            <p class="flex w-full items-center gap-2">
                                <data
                                    class="text-base font-semibold tracking-tighter text-black lg:text-lg"
                                    :value="product.price"
                                    x-text="formatPrice(product.price)"
                                ></data>
                            </p>
                        </template>
                    </div>
                    <template x-if="product.rating > 0">
                        <div class="mt-0.5 flex items-center">
                            <svg
                                class="size-4 shrink-0 text-yellow-500"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                                />
                            </svg>
                            <span
                                class="ml-2 text-xs tracking-tighter text-black/70"
                                x-text="`(${parseFloat(product.rating)})`"
                            ></span>
                        </div>
                    </template>
                </div>
            </article>
        </template>
    </div>
    <div
        class="container mx-auto flex max-w-md items-center justify-end gap-x-3 px-6 pb-3 pt-6 md:max-w-[96rem] md:px-12"
    >
        <x-common.button variant="secondary" class="!p-2" aria-label="Slide sebelumya" x-on:click="previous">
            <svg
                class="size-6 shrink-0"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="m15 18-6-6 6-6" />
            </svg>
        </x-common.button>
        <x-common.button variant="secondary" class="!p-2" aria-label="Slide selanjutnya" x-on:click="next">
            <svg
                class="size-6 shrink-0"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="m9 18 6-6-6-6" />
            </svg>
        </x-common.button>
    </div>
</section>
