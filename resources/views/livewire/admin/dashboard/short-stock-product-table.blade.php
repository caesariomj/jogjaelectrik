<?php

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    #[Computed]
    public function shortStockProducts()
    {
        return DB::table('product_variants')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('product_images', 'product_images.product_id', '=', 'products.id')
            ->join('variant_combinations', 'variant_combinations.product_variant_id', '=', 'product_variants.id')
            ->join('variation_variants', 'variant_combinations.variation_variant_id', '=', 'variation_variants.id')
            ->join('variations', 'variation_variants.variation_id', '=', 'variations.id')
            ->where('product_images.is_thumbnail', true)
            ->where('product_variants.stock', '<=', 10)
            ->select([
                'products.name as name',
                'product_images.file_name as thumbnail',
                'product_variants.stock',
                'variation_variants.name as variant',
                'variations.name as variation',
            ])
            ->limit(10)
            ->orderBy('product_variants.stock', 'asc')
            ->get();
    }
}; ?>

<div>
    @if ($this->shortStockProducts)
        <div class="max-h-[25rem] overflow-x-auto overflow-y-auto">
            <table class="min-w-full text-left text-sm tracking-tight text-black">
                <thead class="sticky top-0 border-b bg-white font-medium text-black/70">
                    <tr>
                        <th class="min-w-8 whitespace-nowrap py-2 pr-4" align="center">No.</th>
                        <th class="min-w-28 whitespace-nowrap px-4 py-2">Nama</th>
                        <th class="min-w-16 whitespace-nowrap px-4 py-2">Variasi</th>
                        <th class="min-w-10 whitespace-nowrap py-2 pl-4" align="center">Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-300">
                    @foreach ($this->shortStockProducts as $product)
                        <tr class="hover:bg-neutral-50">
                            <td class="py-3 pr-4" align="center">{{ $loop->iteration }}.</td>
                            <td class="flex flex-row items-center gap-x-2 px-4 py-3 font-semibold">
                                @if ($product->thumbnail)
                                    <div class="flex h-full shrink-0 items-center">
                                        <div
                                            class="aspect-square size-12 shrink-0 overflow-hidden rounded-md border border-neutral-300"
                                        >
                                            <img
                                                src="{{ asset('storage/uploads/product-images/' . $product->thumbnail) }}"
                                                class="h-full w-full object-cover"
                                                alt="Gambar utama produk {{ $product->name }}"
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
                                {{ ucwords($product->name) }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($product->variant && $product->variation)
                                    {{ ucwords($product->variation) . ' : ' . ucwords($product->variant) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 pl-4" align="center">
                                {{ $product->stock }}
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
                <h3 class="mb-3 text-center text-xl text-black">Produk Tidak Ditemukan</h3>
                <p class="text-center text-sm font-normal tracking-tight text-black/70">
                    Produk dengan stok menipis tidak ditemukan
                </p>
            </div>
        </div>
    @endif
</div>
