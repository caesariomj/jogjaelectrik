<div aria-label="Notifikasi" role="region" class="pointer-events-none sticky inset-x-0 top-4 z-50 px-4 sm:px-6 lg:px-8">
    <div class="pointer-events-auto mx-auto max-w-2xl">
        @if (session()->has('success'))
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition:enter="transform transition duration-300"
                x-transition:enter-start="translate-y-[-100%] opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transform transition duration-300"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-[-100%] opacity-0"
                x-init="setTimeout(() => (show = false), 3000)"
                role="alert"
                aria-live="polite"
                aria-atomic="true"
                class="mb-4 rounded-md border border-teal-400 bg-teal-50 p-4 shadow-md"
            >
                <div class="flex items-center">
                    <div class="flex flex-grow items-center" role="presentation">
                        <div class="flex-shrink-0" aria-hidden="true">
                            <svg class="size-5 text-teal-400" viewBox="0 0 20 20" fill="currentColor">
                                <title>Ikon notifikasi sukses</title>
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div role="heading" aria-level="2" class="ml-3 text-sm font-medium text-teal-800">
                            {{ session('success') }}
                        </div>
                    </div>
                    <div class="ml-auto pl-3" role="navigation">
                        <button
                            @click="show = false"
                            type="button"
                            class="inline-flex rounded-full p-1.5 text-teal-500 hover:bg-teal-100 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"
                            aria-label="Tutup pesan notifikasi sukses"
                        >
                            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <title>Tutup</title>
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
        @endif

        @if (session()->has('error'))
            <div
                x-data="{ show: true }"
                x-show="show"
                x-transition:enter="transform transition duration-300"
                x-transition:enter-start="translate-y-[-100%] opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transform transition duration-300"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-[-100%] opacity-0"
                x-init="setTimeout(() => (show = false), 3000)"
                role="alert"
                aria-live="assertive"
                aria-atomic="true"
                class="mb-4 rounded-md border border-red-400 bg-red-50 p-4 shadow-md"
            >
                <div class="flex items-center">
                    <div class="flex flex-grow items-center" role="presentation">
                        <div class="flex-shrink-0" aria-hidden="true">
                            <svg class="size-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <title>Ikon notifikasi gagal</title>
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div role="heading" aria-level="2" class="ml-3 text-sm font-medium text-red-800">
                            {{ session('error') }}
                        </div>
                    </div>
                    <div class="ml-auto pl-3" role="navigation">
                        <button
                            @click="show = false"
                            type="button"
                            class="inline-flex rounded-full p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                            aria-label="Tutup pesan notifikasi gagal"
                        >
                            <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <title>Tutup</title>
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
        @endif
    </div>
</div>
