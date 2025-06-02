<?php

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    #[Computed]
    public function latestReviews()
    {
        return DB::table('product_reviews')
            ->join('order_details', 'product_reviews.order_detail_id', '=', 'order_details.id')
            ->join('product_variants', 'order_details.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('product_images', 'products.id', '=', 'product_images.product_id')
            ->join('users', 'product_reviews.user_id', '=', 'users.id')
            ->select([
                'products.name as product_name',
                'product_images.file_name as product_thumbnail',
                'product_reviews.rating',
                'product_reviews.review',
                'users.name as user_name',
            ])
            ->where('product_images.is_thumbnail', true)
            ->limit(10)
            ->get();
    }
}; ?>

<div>
    @if ($this->latestReviews)
        <div class="max-h-[25rem] overflow-x-auto overflow-y-auto">
            <table class="min-w-full text-left text-sm tracking-tight text-black">
                <thead class="sticky top-0 border-b bg-white font-medium text-black/70">
                    <tr>
                        <th class="min-w-8 whitespace-nowrap py-2 pr-4" align="center">No.</th>
                        <th class="min-w-40 whitespace-nowrap px-4 py-2">Nama Produk</th>
                        <th class="min-w-28 whitespace-nowrap px-4 py-2">Nama Pelanggan</th>
                        <th class="min-w-16 whitespace-nowrap px-4 py-2">Rating</th>
                        <th class="min-w-36 whitespace-nowrap py-2 pl-4">Ulasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-300">
                    @foreach ($this->latestReviews as $review)
                        <tr class="hover:bg-neutral-50">
                            <td class="py-3 pr-4" align="center">{{ $loop->iteration }}.</td>
                            <td class="flex flex-row items-center gap-x-2 px-4 py-3 font-semibold">
                                @if ($review->product_thumbnail)
                                    <div class="flex h-full shrink-0 items-center">
                                        <div
                                            class="aspect-square size-12 shrink-0 overflow-hidden rounded-md border border-neutral-300"
                                        >
                                            <img
                                                src="{{ asset('storage/uploads/product-images/' . $review->product_thumbnail) }}"
                                                class="h-full w-full object-cover"
                                                alt="Gambar utama produk {{ $review->product_name }}"
                                                loading="lazy"
                                            />
                                        </div>
                                    </div>
                                @else
                                    <div class="flex h-full shrink-0 items-center">
                                        <div
                                            class="flex aspect-square size-12 shrink-0 items-center justify-center rounded-md bg-neutral-200"
                                        >
                                            <x-common.application-logo class="size-8 text-black opacity-50" />
                                        </div>
                                    </div>
                                @endif
                                {{ ucwords($review->product_name) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $review->user_name }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex h-full items-center gap-x-1">
                                    <div class="flex items-center gap-x-0.5">
                                        @for ($i = 0; $i < $review->rating; $i++)
                                            <svg
                                                class="size-3 text-yellow-500"
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
                                        @endfor

                                        @for ($i = 0 + $review->rating; $i < 5; $i++)
                                            <svg
                                                class="size-3 text-black opacity-20"
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
                                        @endfor
                                    </div>
                                    <p class="ml-2 text-sm tracking-tighter text-black/70">({{ $review->rating }})</p>
                                </div>
                            </td>
                            <td class="py-3 pl-4">
                                {{ $review->review }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="mb-4 flex h-full flex-col items-center justify-center">
            <div class="mb-6 size-72">
                {!! file_get_contents(public_path('images/illustrations/empty.svg')) !!}
            </div>
            <div class="flex flex-col items-center">
                <h3 class="mb-3 text-center text-xl text-black">Ulasan Terbaru Tidak Ditemukan</h3>
                <p class="text-center text-sm font-normal tracking-tight text-black/70">
                    Ulasan terbaru dari pelanggan tidak ditemukan
                </p>
            </div>
        </div>
    @endif
</div>
