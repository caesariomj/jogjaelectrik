@props([
    'product',
])

<article {{ $attributes->merge(['class' => 'group']) }}>
    <div class="relative mb-4 h-auto w-full overflow-hidden rounded-lg">
        <a href="{{ route('products.detail', ['slug' => $product->slug]) }}" wire:navigate>
            <img
                src="{{ asset('storage/uploads/product-images/' .$product->images()->thumbnail()->first()->file_name,) }}"
                alt="Gambar produk {{ $product->name }}"
                class="aspect-square h-full w-full scale-100 object-cover transition-transform group-hover:scale-105"
                loading="lazy"
            />
            <div class="absolute inset-0 z-[1] bg-gradient-to-t from-black to-transparent opacity-5"></div>
        </a>
    </div>
    <div class="flex flex-row items-start gap-2">
        <div class="flex-grow">
            <a href="{{ route('products.detail', ['slug' => $product->slug]) }}" wire:navigate>
                <h3
                    class="mb-2 text-base font-semibold text-black transition-colors group-hover:text-primary sm:text-lg lg:text-xl"
                >
                    {{ $product->name }}
                </h3>
            </a>

            @if ($product->base_price_discount)
                <p class="flex w-full flex-wrap items-center gap-2">
                    <data
                        class="text-base font-semibold tracking-tighter text-black sm:text-lg"
                        value="{{ $product->base_price_discount }}"
                    >
                        Rp {{ formatPrice($product->base_price_discount) }}
                    </data>
                    <del class="text-sm tracking-tighter text-black/60">
                        Rp {{ formatPrice($product->base_price) }}
                    </del>
                </p>
            @else
                <p class="flex w-full items-center gap-2">
                    <data
                        value="{{ $product->base_price }}"
                        class="text-base font-semibold tracking-tighter text-black sm:text-lg"
                    >
                        Rp {{ formatPrice($product->base_price) }}
                    </data>
                </p>
            @endif
        </div>
        @if ($product->reviews->avg('rating') > 0)
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
                <span class="ml-2 text-sm tracking-tighter text-black">({{ $product->reviews->avg('rating') }})</span>
            </div>
        @endif
    </div>
</article>
