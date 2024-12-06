@props([
    'disabled' => false,
    'hasError' => false,
])

<input
    {{
        $attributes->merge([
            'class' => 'py-3 px-4 text-sm rounded-md shadow-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed ' . ($hasError ? 'border-red-500' : 'border-neutral-300'),
        ])
    }}
    @disabled($disabled)
/>
