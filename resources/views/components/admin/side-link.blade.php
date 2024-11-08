@props([
    'active',
])

@php
    $classes =
        $active ?? false
            ? 'flex items-center gap-x-3 rounded-lg bg-primary px-4 py-2 text-sm font-medium tracking-tight text-white'
            : 'flex items-center gap-x-3 rounded-lg px-4 py-2 text-sm font-medium tracking-tight transition-all hover:bg-primary-50 hover:text-primary active:ring-2 active:ring-primary-200';
    // top active state, bottom inactive state
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
