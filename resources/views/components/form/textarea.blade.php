@props([
    'disabled' => false,
    'hasError' => false,
])

<textarea
    {{
        $attributes->merge([
            'class' => 'block p-2.5 w-full text-sm shadow-sm text-black rounded-lg border focus:ring-primary focus:border-primary disabled:opacity-50 disabled:cursor-not-allowed ' . ($hasError ? 'border-red-500' : 'border-neutral-300'),
        ])
    }}
    @disabled($disabled)
></textarea>
