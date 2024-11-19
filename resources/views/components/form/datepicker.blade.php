@props([
    'disabled' => false,
    'minDate' => 'today',
])

<div
    x-data="flatpickr(@entangle($attributes->wire('model')), { minDate: '{{ $minDate }}' })"
    class="relative"
    wire:ignore
>
    <input
        {{ $attributes->merge(['class' => 'pe-12 rounded-md cursor-pointer shadow-sm border-neutral-300 focus:border-primary focus:ring-primary']) }}
        @disabled($disabled)
        x-ref="input"
    />
    <button
        type="button"
        x-show="value"
        @click="$refs.input._flatpickr.clear()"
        class="absolute inset-y-0 end-9 flex items-center px-2 text-red-500"
    >
        <svg
            class="size-4"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
        </svg>
    </button>
    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4 text-black opacity-75">
        <svg
            class="size-5"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M8 2v4" />
            <path d="M16 2v4" />
            <rect width="18" height="18" x="3" y="4" rx="2" />
            <path d="M3 10h18" />
            <path d="M8 14h.01" />
            <path d="M12 14h.01" />
            <path d="M16 14h.01" />
            <path d="M8 18h.01" />
            <path d="M12 18h.01" />
            <path d="M16 18h.01" />
        </svg>
    </div>
</div>
