@props([
    'messages',
])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-500 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li class="inline-flex items-start gap-x-1">
                <svg
                    class="mt-0.5 size-4 shrink-0 fill-red-500 stroke-white"
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" x2="12" y1="8" y2="12" />
                    <line x1="12" x2="12.01" y1="16" y2="16" />
                </svg>
                {{ $message }}
            </li>
        @endforeach
    </ul>
@endif
