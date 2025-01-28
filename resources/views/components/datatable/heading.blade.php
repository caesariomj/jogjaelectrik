@props([
    'sortable' => false,
    'direction' => null,
])

<th scope="row" {{ $attributes->only(['class', 'align']) }}>
    @if ($sortable)
        <button
            type="button"
            class="flex w-full items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black underline-offset-1 hover:underline"
            {{ $attributes->except('class') }}
        >
            {{ $slot }}
            <svg class="w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
                <rect width="256" height="256" fill="none" />
                <polyline
                    @class([
                        'text-black/70',
                        'text-primary' => $direction === 'desc',
                    ])
                    points="80 176 128 224 176 176"
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="16"
                />
                <polyline
                    @class([
                        'text-black/70',
                        'text-primary' => $direction === 'asc',
                    ])
                    points="80 80 128 32 176 80"
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="16"
                />
            </svg>
        </button>
    @else
        <span class="flex items-center gap-x-2 p-4 text-sm font-semibold tracking-tight text-black">
            {{ $slot }}
        </span>
    @endif
</th>
