<a
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-x-3 w-full px-4 py-2 text-start text-sm leading-5 text-black font-medium hover:bg-neutral-100 focus:outline-none focus:bg-neutral-100 transition duration-150 ease-in-out']) }}
>
    {{ $slot }}
</a>
