<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public array $labels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    public array $data = [1, 2, 1, 4, 6, 3, 10];

    #[Computed]
    public function salesCount()
    {
        // Implement Google Analytics
    }
}; ?>

<div class="relative h-full w-full overflow-hidden">
    <div
        x-data="chart({
                    labels: @js($labels),
                    data: @js($data),
                    datasetLabel: 'Trafik',
                })"
        class="h-full w-full"
    >
        <canvas x-ref="canvas"></canvas>
    </div>
</div>
