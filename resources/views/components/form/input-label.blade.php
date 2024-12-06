@props([
    'value',
    'required' => true,
])

<label {{ $attributes->merge(['class' => 'block font-medium tracking-tight text-sm text-black']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-red-500">*</span>
    @endif
</label>
