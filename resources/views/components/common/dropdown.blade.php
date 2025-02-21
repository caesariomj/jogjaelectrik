@props([
    'align' => 'right',
    'width' => '',
    'contentClasses' => 'bg-white py-1',
    'placement' => null,
])

@php
    $width = match ($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        '72' => 'w-72',
        default => 'w-48',
    };

    $defaultPlacement = match ($align) {
        'left' => 'bottom-start',
        'top' => 'top',
        default => 'bottom-end',
    };

    $placement = $placement ?? $defaultPlacement;
@endphp

<div
    x-data="dropdown()"
    class="relative inline-block"
    x-on:click.outside="close"
    x-on:close.stop="close"
    wire:ignore
>
    <div x-on:click="toggle" x-ref="button" class="inline-flex items-center">
        {{ $trigger }}
    </div>
    <div
        x-show="open"
        x-ref="panel"
        x-transition.opacity.duration.200ms
        x-on:click="close"
        class="{{ $width }} absolute z-10 overflow-hidden rounded-md border border-neutral-300 shadow-lg"
        x-cloak
    >
        <div class="{{ $contentClasses }} rounded-md ring-1 ring-black ring-opacity-5">
            {{ $content }}
        </div>
    </div>
</div>
