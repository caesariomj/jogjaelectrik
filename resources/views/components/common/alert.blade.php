<div
    id="alert"
    @class([
        'px-4 py-2 text-white md:px-6',
        'bg-teal-500' => session()->has('success'),
        'bg-red-500' => session()->has('error'),
    ])
    role="alert"
    aria-live="assertive"
    aria-atomic="true"
    x-show="hasSession"
    x-transition:enter="transform transition duration-300 ease-out"
    x-transition:enter-start="-translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transform transition duration-300 ease-in"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="-translate-y-full opacity-0"
    x-cloak
>
    <div class="flex items-center justify-start gap-2">
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="size-5 shrink-0"
            aria-hidden="true"
        >
            @if (session()->has('success'))
                <title>Ikon sukses</title>
                <path
                    fill-rule="evenodd"
                    d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                    clip-rule="evenodd"
                />
            @else
                <title>Ikon gagal</title>
                <path
                    fill-rule="evenodd"
                    d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                    clip-rule="evenodd"
                />
            @endif
        </svg>
        <span class="text-sm font-medium tracking-tight">
            {{ session()->has('success') ? 'Sukses' : 'Gagal' }}! {{ session('success') ?? session('error') }}
        </span>
        <div class="ml-auto">
            <button
                id="close-alert"
                type="button"
                @class([
                    'inline-flex rounded-full p-1.5 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'hover:bg-teal-600 focus:ring-teal-700' => session()->has('success'),
                    'hover:bg-red-600 focus:ring-red-700' => session()->has('error'),
                ])
                aria-label="Tutup pesan notifikasi"
                x-on:click="hasSession = false"
            >
                <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <title>Tutup pesan notifikasi</title>
                    <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
        </div>
    </div>
</div>
