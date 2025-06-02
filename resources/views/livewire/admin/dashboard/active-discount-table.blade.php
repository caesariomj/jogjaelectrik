<?php

use App\Models\Discount;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    #[Computed]
    public function activeDiscounts()
    {
        return Discount::baseQuery(
            columns: [
                'name',
                'code',
                'type',
                'value',
                'max_discount_amount',
                'start_date',
                'end_date',
                'used_count',
                'usage_limit',
            ],
        )
            ->where('is_active', true)
            ->limit(10)
            ->get();
    }
}; ?>

<div>
    @if ($this->activeDiscounts)
        <div class="max-h-[25rem] overflow-x-auto overflow-y-auto">
            <table class="min-w-full text-left text-sm tracking-tight text-black">
                <thead class="border-b font-medium text-black/70">
                    <tr>
                        <th class="min-w-8 whitespace-nowrap py-2 pr-4" align="center">No.</th>
                        <th class="min-w-24 whitespace-nowrap px-4 py-2">Nama</th>
                        <th class="min-w-24 whitespace-nowrap px-4 py-2">Kode</th>
                        <th class="min-w-20 whitespace-nowrap px-4 py-2" align="center">Tipe</th>
                        <th class="min-w-32 whitespace-nowrap px-4 py-2" align="center">Nilai</th>
                        <th class="min-w-44 whitespace-nowrap px-4 py-2">Periode</th>
                        <th class="min-w-20 whitespace-nowrap py-2 pl-4">Penggunaan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-300">
                    @foreach ($this->activeDiscounts as $discount)
                        <tr class="hover:bg-neutral-50">
                            <td class="py-3 pr-4" align="center">{{ $loop->iteration }}.</td>
                            <td class="px-4 py-3 font-semibold">{{ ucwords($discount->name) }}</td>
                            <td class="px-4 py-3">{{ $discount->code }}</td>
                            <td class="px-4 py-3" align="center">
                                <span
                                    class="inline-flex items-center gap-x-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium tracking-tight text-blue-800"
                                >
                                    <span class="inline-block size-1 rounded-full bg-blue-800"></span>
                                    {{ $discount->type === 'fixed' ? 'Nominal' : 'Persentase' }}
                                </span>
                            </td>
                            <td
                                @class([
                                    'px-4 py-3',
                                    'flex w-full flex-col items-center justify-center gap-y-2 px-4 py-3 text-center' => $discount->type === 'percentage',
                                ])
                                align="center"
                            >
                                @if ($discount->type === 'fixed')
                                    Rp
                                @endif

                                {{ formatPrice($discount->value) }}

                                @if ($discount->type === 'percentage')
                                    %
                                    @if ($discount->max_discount_amount)
                                        <small class="font-medium">
                                            (Maks: Rp {{ formatPrice($discount->max_discount_amount) }})
                                        </small>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($discount->start_date && $discount->end_date)
                                    {{ formatDate($discount->start_date) . ' - ' . formatDate($discount->end_date) }}
                                @else
                                    Tidak ditentukan.
                                @endif
                            </td>
                            <td class="py-3 pl-4">
                                {{ $discount->used_count }} /
                                {{ $discount->usage_limit ?? 'Tidak ada batasan penggunaan.' }}
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
                <h3 class="mb-3 text-center text-xl text-black">Diskon Tidak Ditemukan</h3>
                <p class="text-center text-sm font-normal tracking-tight text-black/70">
                    Diskon yang sedang aktif tidak ditemukan
                </p>
            </div>
        </div>
    @endif
</div>
