<?php

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    #[Computed]
    public function orderCount(): array
    {
        $statuses = ['waiting_payment', 'payment_received', 'processing', 'shipping'];

        $counts = Order::select('status', DB::raw('count(*) as total'))
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect($statuses)
            ->mapWithKeys(function ($status) use ($counts) {
                return [$status => $counts[$status] ?? 0];
            })
            ->toArray();
    }
}; ?>

<div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <a
            class="group flex flex-row rounded-xl border bg-white p-4 shadow transition-all hover:border-primary hover:shadow-md"
            href="{{ route('admin.orders.index') }}?status=waiting_payment"
            wire:navigate
        >
            <div class="flex flex-col">
                <h3 class="text-sm font-medium text-black/70 group-hover:text-primary">Menunggu Pembayaran</h3>
                <p class="text-xl font-semibold tracking-tight text-black group-hover:text-primary">
                    {{ $this->orderCount['waiting_payment'] }} Pesanan
                </p>
            </div>
            <svg
                class="ms-auto size-5 text-black/70 transition-colors group-hover:text-primary"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
        <a
            class="group flex flex-row rounded-xl border bg-white p-4 shadow transition-all hover:border-primary hover:shadow-md"
            href="{{ route('admin.orders.index') }}?status=payment_received"
            wire:navigate
        >
            <div class="flex flex-col">
                <h3 class="text-sm font-medium text-black/70 group-hover:text-primary">Menunggu Diproses</h3>
                <p class="text-xl font-semibold tracking-tight text-black group-hover:text-primary">
                    {{ $this->orderCount['payment_received'] }} Pesanan
                </p>
            </div>
            <svg
                class="ms-auto size-5 text-black/70 transition-colors group-hover:text-primary"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
        <a
            class="group flex flex-row rounded-xl border bg-white p-4 shadow transition-all hover:border-primary hover:shadow-md"
            href="{{ route('admin.orders.index') }}?status=processing"
            wire:navigate
        >
            <div class="flex flex-col">
                <h3 class="text-sm font-medium text-black/70 group-hover:text-primary">Menunggu Dikirim</h3>
                <p class="text-xl font-semibold tracking-tight text-black group-hover:text-primary">
                    {{ $this->orderCount['processing'] }} Pesanan
                </p>
            </div>
            <svg
                class="ms-auto size-5 text-black/70 transition-colors group-hover:text-primary"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
        <a
            class="group flex flex-row rounded-xl border bg-white p-4 shadow transition-all hover:border-primary hover:shadow-md"
            href="{{ route('admin.orders.index') }}?status=shipping"
            wire:navigate
        >
            <div class="flex flex-col">
                <h3 class="text-sm font-medium text-black/70 group-hover:text-primary">Dalam Pengiriman</h3>
                <p class="text-xl font-semibold tracking-tight text-black group-hover:text-primary">
                    {{ $this->orderCount['shipping'] }} Pesanan
                </p>
            </div>
            <svg
                class="ms-auto size-5 text-black/70 transition-colors group-hover:text-primary"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
        </a>
    </div>
</div>
