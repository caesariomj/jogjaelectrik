@props([
    'disabled' => false,
    'error' => false,
])

<textarea
    {{ $attributes->merge(['class' => 'block p-2.5 w-full text-black rounded-lg border border-neutral-300 focus:ring-primary focus:border-primary']) }}
    @disabled($disabled)
>
{{ $slot }}</textarea
>
