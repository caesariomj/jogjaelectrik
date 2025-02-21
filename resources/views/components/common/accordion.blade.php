@props([
    'title',
    'expanded' => false,
])

@php
    $id = strtolower(str_replace(' ', '-', $title));
@endphp

<div x-data="{ isExpanded: {{ $expanded ? 'true' : 'false' }} }">
    <button
        id="controls-{{ $id }}-accordion"
        type="button"
        {{ $attributes->merge(['class' => 'flex w-full items-center justify-between gap-4 py-4 text-left text-lg tracking-tight text-black underline-offset-2 focus-visible:underline focus-visible:outline-none lg:text-xl']) }}
        :class="isExpanded ? 'font-semibold'  : 'font-medium'"
        aria-controls="{{ $id }}-accordion"
        :aria-expanded="isExpanded ? 'true' : 'false'"
        x-on:click="isExpanded = ! isExpanded"
    >
        {{ $title ?? 'Accordion' }}
        <svg
            class="size-5 shrink-0 transition-transform"
            :class="isExpanded  ?  'rotate-180'  :  ''"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke-width="2"
            stroke="currentColor"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>
    <div
        id="{{ $id }}-accordion"
        role="region"
        aria-labelledby="controls-{{ $id }}-accordion"
        x-show="isExpanded"
        x-cloak
        x-collapse
    >
        {{ $slot }}
    </div>
</div>
