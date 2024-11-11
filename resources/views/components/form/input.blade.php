@props(['disabled' => false])

<input
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'rounded-md shadow-sm border-neutral-300 focus:border-primary focus:ring-primary']) }}
/>
