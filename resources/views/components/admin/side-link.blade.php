@props([
    'active',
])

@php
    $classes =
        $active ?? false
            ? 'relative flex items-center gap-x-3 rounded-lg bg-primary-50 px-4 py-2 text-sm font-medium tracking-tight text-primary before:absolute before:bottom-0 before:left-0 before:top-1/2 before:h-4 before:w-1 before:-translate-y-1/2 before:rounded-r-md before:bg-primary'
            : 'flex items-center gap-x-3 rounded-lg px-4 py-2 text-sm font-medium tracking-tight text-black transition-all hover:bg-primary-50 hover:text-primary active:ring-2 active:ring-primary-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
