@props([
    'disabled' => false,
    'hasError' => false,
])

<input
    @disabled($disabled)
    {{
        $attributes->merge([
            'class' => 'rounded-md shadow-sm focus:border-primary focus:ring-primary ' . ($hasError ? 'border-red-500' : 'border-neutral-300'),
        ])
    }}
/>
