<?php

use App\Models\Order;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public array $labels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    #[Computed]
    public function salesCount()
    {
        $now = Carbon::now();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();

        // Initialize array with days as keys and 0 as values
        $salesCount = array_fill(0, 7, 0);

        // Get daily counts directly from the database using more efficient grouping
        $dailyCounts = Order::selectRaw('DATE(created_at) as order_date, COUNT(*) as count')
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->groupBy('order_date')
            ->get()
            ->keyBy('order_date');

        // Map the results to our array
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart
                ->copy()
                ->addDays($i)
                ->format('Y-m-d');
            $salesCount[$i] = $dailyCounts->get($date, ['count' => 0])['count'] ?? 0;
        }

        return $salesCount;
    }
}; ?>

<div class="relative h-full w-full overflow-hidden">
    <div
        x-data="chart({
                    labels: @js($labels),
                    data: @js($this->salesCount),
                    datasetLabel: 'Total Pesanan',
                })"
        class="h-full w-full"
    >
        <canvas x-ref="canvas"></canvas>
    </div>
</div>
