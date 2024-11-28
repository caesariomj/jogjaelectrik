@props([
    'images',
])

@php
    $thumbnail = $images->filter(function ($image) {
        return $image->is_thumbnail;
    });

    $images = $images->filter(function ($image) {
        return ! $image->is_thumbnail;
    });

    $productName = $images->first()->product->name;
@endphp

<section
    class="relative flex w-full flex-col-reverse gap-2 overflow-y-hidden lg:sticky lg:top-[4.5rem] lg:h-[38rem] lg:w-1/2 lg:flex-row lg:gap-4"
    x-data="productGallery(
                '{{ asset('uploads/product-images/' . $thumbnail->first()->file_name) }}',
            )"
    x-init="init()"
>
    <div class="relative h-full">
        <ul
            class="flex h-full flex-row flex-wrap gap-2 transition-transform lg:flex-col lg:flex-nowrap"
            aria-label="List gambar produk {{ $productName }}"
            x-ref="slider"
            :style="`transform: translateY(-${scrollPosition}px)`"
        >
            <li>
                <button
                    aria-label="Tampilkan gambar utama produk {{ $productName }} - 1"
                    class="group size-20 overflow-hidden rounded-xl border border-neutral-300 transition-colors hover:border-primary"
                    :class="{'border-primary': selectedImage === '{{ asset('uploads/product-images/' . $thumbnail->first()->file_name) }}'}"
                    x-on:click="
                        selectImage(
                            '{{ asset('uploads/product-images/' . $thumbnail->first()->file_name) }}',
                        )
                    "
                >
                    <img
                        src="{{ asset('uploads/product-images/' . $thumbnail->first()->file_name) }}"
                        alt="Gambar produk {{ $productName }} - 1"
                        class="h-auto w-full object-cover brightness-100 group-hover:brightness-95"
                        :class="{'brightness-95': selectedImage === '{{ asset('uploads/product-images/' . $thumbnail->first()->file_name) }}'}"
                    />
                </button>
            </li>
            @foreach ($images as $image)
                <li>
                    <button
                        aria-label="Tampilkan gambar utama produk {{ $productName . ' - ' . $loop->index + 2 }}"
                        class="group size-20 overflow-hidden rounded-xl border border-neutral-300 transition-colors hover:border-primary"
                        :class="{'border-primary': selectedImage === '{{ asset('uploads/product-images/' . $image->file_name) }}'}"
                        x-on:click="selectImage('{{ asset('uploads/product-images/' . $image->file_name) }}')"
                    >
                        <img
                            src="{{ asset('uploads/product-images/' . $image->file_name) }}"
                            alt="Gambar produk {{ $productName . ' - ' . $loop->index + 2 }}"
                            class="h-auto w-full object-cover brightness-100 group-hover:brightness-95"
                            :class="{'brightness-95': selectedImage === '{{ asset('uploads/product-images/' . $image->file_name) }}'}"
                            loading="lazy"
                        />
                    </button>
                </li>
            @endforeach
        </ul>
        @if ($images->count() > 6)
            <button
                x-on:click="scrollUp"
                x-show="!isAtTop"
                x-transition
                class="absolute inset-x-0 top-0 hidden justify-center bg-gradient-to-t from-transparent to-white to-50% px-2 py-8 text-black hover:text-primary lg:flex"
                x-cloak
            >
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
                >
                    <path d="m18 15-6-6-6 6" />
                </svg>
            </button>
            <button
                x-on:click="scrollDown"
                x-show="!isAtBottom"
                x-transition.opacity
                class="absolute inset-x-0 bottom-0 hidden justify-center bg-gradient-to-b from-transparent to-white to-50% px-2 py-8 text-black hover:text-primary lg:flex"
                x-cloak
            >
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
                >
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
        @endif
    </div>
    <figure class="relative mb-4 flex-grow overflow-hidden rounded-xl border border-neutral-300 lg:mb-0">
        <img
            :src="selectedImage"
            x-transition:enter="transition-opacity duration-500 ease-in-out"
            x-transition:leave="transition-opacity duration-500 ease-in-out"
            class="h-full w-full object-cover"
            alt="Deskripsi gambar utama produk"
        />
        <figcaption class="sr-only">Gambar utama produk</figcaption>
    </figure>
</section>

@push('scripts')
    <script>
        function productGallery(initialImage) {
            return {
                scrollPosition: 0,
                itemHeight: 0,
                sliderHeight: 0,
                totalHeight: 0,
                isAtTop: true,
                isAtBottom: false,
                selectedImage: initialImage,

                init() {
                    this.itemHeight = this.$refs.slider.querySelector('li').offsetHeight + 8;
                    this.sliderHeight = this.$refs.slider.offsetHeight;
                    this.totalHeight = this.itemHeight * this.$refs.slider.children.length;

                    this.updateButtonVisibility();
                },

                scrollUp() {
                    if (this.scrollPosition > 0) {
                        this.scrollPosition -= this.itemHeight;
                        this.updateButtonVisibility();
                    }
                },

                scrollDown() {
                    if (this.scrollPosition + this.sliderHeight < this.totalHeight) {
                        this.scrollPosition += this.itemHeight;
                        this.updateButtonVisibility();
                    }
                },

                updateButtonVisibility() {
                    this.isAtTop = this.scrollPosition <= 0;
                    this.isAtBottom = this.scrollPosition + this.sliderHeight >= this.totalHeight;
                },

                selectImage(imageUrl) {
                    this.selectedImage = imageUrl;
                },
            };
        }
    </script>
@endpush
