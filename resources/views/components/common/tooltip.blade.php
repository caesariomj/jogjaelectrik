@props([
    'id',
    'text',
])

<div x-data="{ showTooltip: false }" class="relative" x-bind:aria-expanded="showTooltip">
    <button
        type="button"
        class="mt-1 cursor-pointer rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black"
        aria-describedby="tooltip-{{ $id ?? 'default' }}"
        x-on:click="showTooltip = !showTooltip"
    >
        <svg class="size-5 fill-black/80" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 256 256">
            <path
                d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,168a12,12,0,1,1,12-12A12,12,0,0,1,128,192Zm8-48.72V144a8,8,0,0,1-16,0v-8a8,8,0,0,1,8-8c13.23,0,24-9,24-20s-10.77-20-24-20-24,9-24,20v4a8,8,0,0,1-16,0v-4c0-19.85,17.94-36,40-36s40,16.15,40,36C168,125.38,154.24,139.93,136,143.28Z"
            />
        </svg>
    </button>
    <div
        id="tooltip-{{ $id ?? 'default' }}"
        class="absolute -top-9 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded bg-black px-2 py-1 text-center text-sm text-white"
        role="tooltip"
        x-show="showTooltip"
        x-on:click.outside="showTooltip = false"
        x-transition:enter="transition ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-out"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
    >
        {{ $text }}
    </div>
</div>
