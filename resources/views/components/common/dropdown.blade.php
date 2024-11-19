@props([
    'align' => 'right',
    'width' => '48',
    'contentClasses' => 'bg-white py-1',
    'placement' => null,
])

@php
    $width = 'w-' . $width;

    $defaultPlacement = match ($align) {
        'left' => 'bottom-start',
        'top' => 'top',
        default => 'bottom-end',
    };

    $placement = $placement ?? $defaultPlacement;
@endphp

<div
    x-data="dropdown()"
    @click.outside="close"
    @close.stop="close"
    data-placement="{{ $placement }}"
    class="relative inline-block"
    wire:ignore
>
    <div @click="toggle" x-ref="button" class="inline-flex items-center">
        {{ $trigger }}
    </div>
    <div
        x-show="open"
        x-ref="panel"
        x-transition.opacity.duration.200ms
        @click="close"
        class="{{ $width }} absolute z-10 rounded-md shadow-lg"
        x-cloak
    >
        <div class="{{ $contentClasses }} rounded-md bg-white ring-1 ring-black ring-opacity-5">
            {{ $content }}
        </div>
    </div>
</div>
