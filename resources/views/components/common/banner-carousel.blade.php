@props([
    'slides' => [],
    'autoplayInterval' => 0,
])

<div
    x-data="bannerCarousel({
                slides: @js($slides),
                intervalTime: {{ $autoplayInterval }},
            })"
    {{ $attributes->merge(['class' => 'relative w-full overflow-hidden']) }}
>
    <div
        class="relative min-h-[75svh] w-full md:min-h-[95svh]"
        x-on:touchstart="handleTouchStart($event)"
        x-on:touchmove="handleTouchMove($event)"
        x-on:touchend="handleTouchEnd()"
    >
        <template x-for="(slide, index) in slides">
            <figure
                class="absolute inset-0 overflow-hidden"
                x-show="currentSlideIndex == index + 1"
                x-transition.opacity.duration.1000ms
                x-cloak
            >
                <img
                    class="absolute inset-0 h-full w-full object-cover text-neutral-600"
                    x-bind:src="slide.imgSrc"
                    x-bind:alt="slide.imgAlt"
                />
                <div class="absolute inset-0 z-[1] bg-gradient-to-t from-black/90 to-transparent"></div>
                <figcaption
                    class="absolute left-0 top-1/2 z-[2] flex -translate-y-1/2 flex-col items-center justify-end p-8 text-center md:bottom-0 md:-translate-y-12 md:items-start md:p-20 md:text-start"
                >
                    <template x-if="index === 0">
                        <h1
                            class="mb-4 text-white md:mb-8 md:!text-6xl"
                            x-text="slide.title"
                            x-bind:aria-describedby="'slide' + (index + 1) + 'Description'"
                        ></h1>
                    </template>
                    <template x-if="index !== 0">
                        <p
                            class="mb-4 text-[2.5rem] font-bold tracking-tight text-white md:mb-8 md:!text-6xl"
                            x-text="slide.title"
                            x-bind:aria-describedby="'slide' + (index + 1) + 'Description'"
                        ></p>
                    </template>
                    <p
                        class="mb-8 w-full text-pretty text-lg text-white/80 md:mb-16 md:w-1/2 md:text-xl"
                        x-bind:id="'slide' + (index + 1) + 'Description'"
                        x-text="slide.description"
                    ></p>
                    <a
                        class="inline-flex items-center justify-center gap-x-2 rounded-full bg-primary px-8 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2"
                        x-bind:href="slide.ctaUrl"
                        wire:navigate
                    >
                        <span x-text="slide.ctaText"></span>
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
                    </a>
                </figcaption>
            </figure>
        </template>
    </div>
    <div
        class="absolute bottom-4 left-1/2 z-[2] flex -translate-x-1/2 gap-4 md:gap-3 md:px-2"
        role="group"
        aria-label="slides"
    >
        <template x-for="(slide, index) in slides">
            <button
                class="size-2 cursor-pointer rounded-full transition-colors"
                x-on:click="currentSlideIndex = index + 1"
                x-bind:class="[currentSlideIndex === index + 1 ? 'bg-white' : 'bg-white/50']"
                x-bind:aria-label="'slide ' + (index + 1)"
            ></button>
        </template>
    </div>
    <nav class="absolute bottom-4 end-24 z-[2] hidden gap-x-4 md:flex">
        <button
            type="button"
            class="relative rounded-full bg-white p-2 text-black transition-colors hover:bg-neutral-100"
            aria-label="Slide sebelumnya"
            x-on:click="previous()"
        >
            <svg
                class="size-6 pr-0.5"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                stroke="currentColor"
                fill="none"
                stroke-width="2"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
        </button>
        <button
            type="button"
            class="relative rounded-full bg-white p-2 text-black transition-colors hover:bg-neutral-100"
            aria-label="Slide selanjutnya"
            x-on:click="next()"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                stroke="currentColor"
                fill="none"
                stroke-width="2"
                class="size-6 pl-0.5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>
    </nav>
</div>
