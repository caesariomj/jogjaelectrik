@props([
    'href' => null,
    'disabled' => false,
])

@if ($href && ! $disabled)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-x-3 w-full px-4 py-2 tracking-tight text-start text-sm leading-5 text-black font-medium transition duration-150 ease-in-out hover:bg-neutral-100 focus:outline-none focus:bg-neutral-100']) }}
    >
        {{ $slot }}
    </a>
@elseif ($href && $disabled)
    <span
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-x-3 w-full px-4 py-2 tracking-tight text-start text-sm leading-5 text-black font-medium cursor-not-allowed opacity-50']) }}
    >
        {{ $slot }}
    </span>
@else
    <button
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-x-3 w-full px-4 py-2 tracking-tight text-start text-sm leading-5 text-black font-medium transition duration-150 ease-in-out hover:bg-neutral-100 focus:outline-none focus:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-50']) }}
        @disabled($disabled)
    >
        {{ $slot }}
    </button>
@endif
