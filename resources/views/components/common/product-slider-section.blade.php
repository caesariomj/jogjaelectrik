@props([
    'title',
    'products',
])

<section
    x-data="productSlider({ products: @js($products) })"
    class="min-h-[75svh] overflow-hidden py-6 lg:py-12"
    aria-label="{{ $title }} Section"
>
    <div class="mb-6 flex items-center justify-between px-4 lg:px-6">
        <h2 class="text-black">{{ $title }}</h2>
        <template x-if="showControls">
            <div class="inline-flex items-center gap-x-4">
                <x-common.button variant="secondary" class="!p-2" aria-label="Slide sebelumya" x-on:click="next">
                    <svg
                        class="size-6 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
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
                        width="24"
                        height="24"
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
        </template>
    </div>
    <div
        class="flex transition-transform duration-500"
        :style="{ transform: `translateX(-${currentIndex * 100}%)` }"
        aria-live="polite"
        x-on:touchstart="handleTouchStart($event)"
        x-on:touchmove="handleTouchMove($event)"
        x-on:touchend="handleTouchEnd()"
    >
        <template x-for="slide in chunkedProducts" :key="slide[0].id">
            <div class="flex min-w-full space-x-4 lg:space-x-6" role="group" aria-roledescription="slide">
                <template x-for="product in slide" :key="product.id">
                    <article
                        class="group w-[calc(50%-1rem)] lg:w-[calc(33.33%-1.5rem)]"
                        :class="product.isPlaceholder ? 'invisible' : ''"
                        :aria-label="!product.isPlaceholder ? `Produk ${product.name}` : ''"
                    >
                        <div x-show="!product.isPlaceholder">
                            <div
                                class="relative mb-4 h-60 w-full overflow-hidden rounded-lg sm:h-80 md:h-96 lg:h-[30rem]"
                            >
                                <a :href="product.link" wire:navigate>
                                    <img
                                        :src="product.thumbnail"
                                        :alt="`Gambar produk ${product.name}`"
                                        class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                        loading="lazy"
                                    />
                                    <div
                                        class="absolute inset-0 z-[1] bg-gradient-to-t from-black to-transparent opacity-5"
                                    ></div>
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
                                                class="text-sm tracking-tighter text-black/60"
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
                                <div class="mt-0.5 flex items-center">
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
                                    >
                                        <path
                                            d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                                        />
                                    </svg>
                                    <span class="ml-2 text-sm tracking-tighter text-black">(4.5)</span>
                                </div>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </template>
    </div>
</section>
