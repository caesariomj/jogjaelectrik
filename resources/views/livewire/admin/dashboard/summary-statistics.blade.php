<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Volt\Component;

new class extends Component {
    public int $todaysOrder = 0;
    public int $pendingRefunds = 0;
    public int $monthlyIncome = 0;
    public int $activeProducts = 0;
    public int $totalUsers = 0;

    public function mount(): void
    {
        $this->countTodaysOrder();
        $this->countPendingRefunds();
        $this->countMonthlyIncome();
        $this->countActiveProducts();
        $this->countTotalUsers();
    }

    private function countTodaysOrder(): void
    {
        $this->todaysOrder = Order::whereDate('created_at', Carbon::today())->count();
    }

    private function countPendingRefunds(): void
    {
        $this->pendingRefunds = Refund::where('status', 'pending')->count();
    }

    private function countMonthlyIncome(): void
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $monthlyIncome = (float) Payment::join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereBetween('payments.created_at', [$monthStart, $monthEnd])
            ->where(function ($query) {
                $query->where('payments.status', 'paid')->orWhere('payments.status', 'settled');
            })
            ->sum('orders.total_amount');

        $this->monthlyIncome = $monthlyIncome;
    }

    private function countActiveProducts(): void
    {
        $this->activeProducts = Product::where('is_active', true)->count();
    }

    private function countTotalUsers(): void
    {
        $this->totalUsers = User::role('user')->count();
    }
}; ?>

<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
    <a
        class="flex items-center justify-between rounded-xl bg-white p-4 shadow transition-shadow hover:shadow-md"
        href="{{ route('admin.orders.index') }}"
        wire:navigate
    >
        <div>
            <p class="text-sm font-medium tracking-tight text-black/70">Total Pesanan Hari Ini</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-black">{{ $todaysOrder }}</p>
        </div>
        <div class="flex size-12 items-center justify-center rounded-lg bg-primary-50 text-primary">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-8 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"
                />
                <path d="M12 22V12" />
                <polyline points="3.29 7 12 12 20.71 7" />
                <path d="m7.5 4.27 9 5.15" />
            </svg>
        </div>
    </a>
    <a
        class="flex items-center justify-between rounded-xl bg-white p-4 shadow transition-shadow hover:shadow-md"
        href="{{ route('admin.refunds.index') }}"
        wire:navigate
    >
        <div>
            <p class="text-sm font-medium tracking-tight text-black/70">Permintaan Refund Tertunda</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-black">{{ $pendingRefunds }}</p>
        </div>
        <div class="flex size-12 items-center justify-center rounded-lg bg-primary-50 text-primary">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-8 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17" />
                <path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9" />
                <path d="m2 16 6 6" />
                <circle cx="16" cy="9" r="2.9" />
                <circle cx="6" cy="5" r="3" />
            </svg>
        </div>
    </a>
    <a
        class="flex items-center justify-between rounded-xl bg-white p-4 shadow transition-shadow hover:shadow-md"
        href="{{ route('admin.sales.index') }}"
        wire:navigate
    >
        <div>
            <p class="text-sm font-medium tracking-tight text-black/70">Total Penjualan Bulan Ini</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-black">Rp {{ formatPrice($monthlyIncome) }}</p>
        </div>
        <div class="flex size-12 items-center justify-center rounded-lg bg-primary-50 text-primary">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-8 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M12 18H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5" />
                <path d="M18 12h.01" />
                <path d="M19 22v-6" />
                <path d="m22 19-3-3-3 3" />
                <path d="M6 12h.01" />
                <circle cx="12" cy="12" r="2" />
            </svg>
        </div>
    </a>
    <a
        class="flex items-center justify-between rounded-xl bg-white p-4 shadow transition-shadow hover:shadow-md"
        href="{{ route('admin.products.index') }}"
        wire:navigate
    >
        <div>
            <p class="text-sm font-medium tracking-tight text-black/70">Jumlah Produk Aktif</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-black">{{ $activeProducts }}</p>
        </div>
        <div class="flex size-12 items-center justify-center rounded-lg bg-primary-50 text-primary">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-8 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
                />
                <path d="m3.3 7 8.7 5 8.7-5" />
                <path d="M12 22V12" />
            </svg>
        </div>
    </a>
    <a
        class="flex items-center justify-between rounded-xl bg-white p-4 shadow transition-shadow hover:shadow-md"
        href="{{ route('admin.users.index') }}"
        wire:navigate
    >
        <div>
            <p class="text-sm font-medium tracking-tight text-black/70">Jumlah Pelanggan</p>
            <p class="mt-1 text-xl font-semibold tracking-tight text-black">{{ $totalUsers }}</p>
        </div>
        <div class="flex size-12 items-center justify-center rounded-lg bg-primary-50 text-primary">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-8 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                />
            </svg>
        </div>
    </a>
</div>
