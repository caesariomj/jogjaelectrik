@props([
    'active',
])

@php
    $classes =
        $active ?? false
            ? 'inline-flex w-full items-center gap-x-3 border-l-4 border-primary bg-primary-50 py-2 pe-4 ps-3 text-start text-sm font-medium tracking-tight text-primary-700 transition duration-150 ease-in-out focus:border-primary focus:bg-primary-50 focus:text-primary-700 focus:outline-none'
            : 'inline-flex w-full items-center gap-x-3 border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-sm font-medium tracking-tight text-black/75 transition duration-150 ease-in-out hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 focus:border-primary-300 focus:bg-primary-50 focus:text-primary-700 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
