@props([
    'inputAttributes' => [],
    'labelAttributes' => [],
    'hasError' => false,
])

<input
    type="radio"
    class="peer hidden"
    {{ $attributes->merge($inputAttributes) }}
    formnovalidate
/>
<label
    {{
        $attributes->merge($labelAttributes)->merge([
            'class' => 'inline-flex w-full shadow-sm cursor-pointer items-center justify-start gap-x-4 rounded-lg border py-3 px-4 hover:bg-neutral-100 peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary ' . ($hasError ? 'bg-red-50 border-red-500' : 'bg-white border-neutral-300'),
        ])
    }}
>
    {{ $slot }}
</label>
