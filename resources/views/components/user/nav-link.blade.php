@props([
    'active',
])

@php
    $classes =
        $active ?? false
            ? 'inline-flex items-center border-b-2 border-primary px-1 pt-1 text-sm font-medium leading-5 tracking-tight text-black transition duration-150 ease-in-out focus:border-primary focus:outline-none'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 tracking-tight text-black/75 transition duration-150 ease-in-out hover:border-primary hover:text-black focus:border-black/75 focus:text-black focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
