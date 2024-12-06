@props([
    'hasError' => false,
])

<input
    type="checkbox"
    {{
        $attributes->merge([
            'class' => 'mt-0.5 shrink-0 rounded text-primary shadow-sm focus:ring-primary disabled:pointer-events-none disabled:opacity-50 ' . ($hasError ? 'border-red-500' : 'border-neutral-300'),
        ])
    }}
/>
