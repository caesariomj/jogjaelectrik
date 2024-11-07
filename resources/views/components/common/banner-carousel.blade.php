@props([
    'slides' => [],
    'autoplayInterval' => 0,
])

<div
    x-data="carousel({
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
                x-show="currentSlideIndex == index + 1"
                class="absolute inset-0 overflow-hidden rounded-3xl"
                x-transition.opacity.duration.1000ms
                x-cloak
            >
                <img
                    x-bind:src="slide.imgSrc"
                    x-bind:alt="slide.imgAlt"
                    class="absolute inset-0 h-full w-full object-cover text-neutral-600"
                />
                <div class="absolute inset-0 z-[1] bg-gradient-to-t from-black/90 to-transparent"></div>
                <figcaption
                    class="absolute left-0 top-1/2 z-[2] flex -translate-y-1/2 flex-col items-center justify-end p-8 text-center md:bottom-0 md:-translate-y-0 md:items-start md:p-20 md:text-start"
                >
                    <template x-if="index === 0">
                        <h1
                            x-text="slide.title"
                            x-bind:aria-describedby="'slide' + (index + 1) + 'Description'"
                            class="mb-4 text-white md:mb-8 md:!text-6xl"
                        ></h1>
                    </template>
                    <template x-if="index !== 0">
                        <p
                            x-text="slide.title"
                            x-bind:aria-describedby="'slide' + (index + 1) + 'Description'"
                            class="mb-4 text-[2.5rem] font-bold tracking-tight text-white md:mb-8 md:!text-6xl"
                        ></p>
                    </template>
                    <p
                        x-bind:id="'slide' + (index + 1) + 'Description'"
                        x-text="slide.description"
                        class="mb-8 w-full text-pretty text-lg text-neutral-200 md:mb-16 md:w-1/2 md:text-xl"
                    ></p>
                    <a
                        x-bind:href="slide.ctaUrl"
                        x-text="slide.ctaText"
                        class="inline-flex items-center justify-center gap-x-2 rounded-full bg-primary px-8 py-3 text-sm font-semibold text-white transition-all hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2"
                    ></a>
                </figcaption>
            </figure>
        </template>
    </div>
    <div
        class="absolute bottom-3 left-1/2 z-[2] flex -translate-x-1/2 gap-4 md:bottom-5 md:gap-3 md:px-2"
        role="group"
        aria-label="slides"
    >
        <template x-for="(slide, index) in slides">
            <button
                class="size-2 cursor-pointer rounded-full transition"
                x-on:click="currentSlideIndex = index + 1"
                x-bind:class="[currentSlideIndex === index + 1 ? 'bg-white' : 'bg-white/50']"
                x-bind:aria-label="'slide ' + (index + 1)"
            ></button>
        </template>
    </div>
    <nav class="absolute -bottom-1 right-0 z-[2] flex gap-x-4 rounded-br-3xl rounded-tl-3xl bg-white p-4">
        <button
            type="button"
            class="relative rounded-full bg-neutral-200 p-2 text-black hover:bg-neutral-300"
            aria-label="previous slide"
            x-on:click="previous()"
        >
            <svg
                class="size-5 pr-0.5"
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
            class="relative rounded-full bg-neutral-200 p-2 text-black hover:bg-neutral-300"
            aria-label="next slide"
            x-on:click="next()"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                stroke="currentColor"
                fill="none"
                stroke-width="2"
                class="size-5 pl-0.5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </button>
    </nav>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data(
                'carousel',
                (
                    data = {
                        slides: [],
                        intervalTime: 0,
                    },
                ) => ({
                    slides: data.slides,
                    autoplayIntervalTime: data.intervalTime,
                    currentSlideIndex: 1,
                    touchStartX: null,
                    touchEndX: null,
                    swipeThreshold: 50,
                    isPaused: false,
                    autoplayInterval: null,

                    init() {
                        if (this.autoplayIntervalTime > 0) {
                            this.autoplay();
                        }
                    },

                    previous() {
                        if (this.currentSlideIndex > 1) {
                            this.currentSlideIndex = this.currentSlideIndex - 1;
                        } else {
                            this.currentSlideIndex = this.slides.length;
                        }
                    },

                    next() {
                        if (this.currentSlideIndex < this.slides.length) {
                            this.currentSlideIndex = this.currentSlideIndex + 1;
                        } else {
                            this.currentSlideIndex = 1;
                        }
                    },

                    handleTouchStart(event) {
                        this.touchStartX = event.touches[0].clientX;
                    },

                    handleTouchMove(event) {
                        this.touchEndX = event.touches[0].clientX;
                    },

                    handleTouchEnd() {
                        if (this.touchEndX) {
                            if (this.touchStartX - this.touchEndX > this.swipeThreshold) {
                                this.next();
                            }
                            if (this.touchStartX - this.touchEndX < -this.swipeThreshold) {
                                this.previous();
                            }
                            this.touchStartX = null;
                            this.touchEndX = null;
                        }
                    },

                    autoplay() {
                        this.autoplayInterval = setInterval(() => {
                            if (!this.isPaused) {
                                this.next();
                            }
                        }, this.autoplayIntervalTime);
                    },

                    setAutoplayIntervalTime(newIntervalTime) {
                        clearInterval(this.autoplayInterval);
                        this.autoplayIntervalTime = newIntervalTime;
                        this.autoplay();
                    },
                }),
            );
        });
    </script>
@endpush
