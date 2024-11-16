<aside
    :class="{'-translate-x-full': !isOpen, 'translate-x-0': isOpen}"
    class="fixed inset-y-0 start-0 z-50 h-screen w-64 transform border-e border-neutral-300 bg-white text-black transition-transform duration-300 ease-in-out lg:translate-x-0"
>
    <div class="flex items-center justify-between p-4">
        <a href="{{ route('home') }}" class="flex w-fit items-center gap-x-4">
            <x-common.application-logo class="block h-9 w-auto fill-current text-primary" />
            <span class="text-xl font-semibold tracking-tight">My App</span>
        </a>
        <button @click="toggleSidebar" class="relative rounded-full p-2 text-black hover:bg-neutral-100 lg:hidden">
            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <div id="sidebar-nav" class="h-[calc(100%-4.2rem)] overflow-y-hidden hover:overflow-y-auto">
        <nav class="px-4 py-2">
            <ul class="space-y-1.5">
                <li>
                    <x-admin.side-link
                        :href="route('admin.dashboard')"
                        :active="request()->routeIs('admin.dashboard')"
                        wire:navigate
                    >
                        <svg
                            class="size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M15.6 2.7a10 10 0 1 0 5.7 5.7" />
                            <circle cx="12" cy="12" r="2" />
                            <path d="M13.4 10.6 19 5" />
                        </svg>
                        Dashboard
                    </x-admin.side-link>
                </li>
                <li class="border-t py-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-black/50">Produk</span>
                </li>
                <li>
                    <x-admin.side-link
                        :href="route('admin.products.index')"
                        :active="request()->routeIs('admin.products.*')"
                        wire:navigate
                    >
                        <svg
                            class="size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
                            />
                            <path d="m3.3 7 8.7 5 8.7-5" />
                            <path d="M12 22V12" />
                        </svg>
                        Produk
                    </x-admin.side-link>
                </li>
                <li>
                    <x-admin.side-link
                        :href="route('admin.archived-products.index')"
                        :active="request()->routeIs('admin.archived-products.*')"
                        wire:navigate
                    >
                        <svg
                            class="size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect width="20" height="5" x="2" y="3" rx="1" />
                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                            <path d="M10 12h4" />
                        </svg>
                        Arsip Produk
                    </x-admin.side-link>
                </li>
                <li>
                    <x-admin.side-link
                        :href="route('admin.categories.index')"
                        :active="request()->routeIs('admin.categories.*')"
                        wire:navigate
                    >
                        <svg
                            class="size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"
                            />
                            <circle cx="7.5" cy="7.5" r=".5" fill="currentColor" />
                        </svg>
                        Kategori
                    </x-admin.side-link>
                </li>
                <li>
                    <x-admin.side-link
                        :href="route('admin.subcategories.index')"
                        :active="request()->routeIs('admin.subcategories.*')"
                        wire:navigate
                    >
                        <svg
                            class="size-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="m15 5 6.3 6.3a2.4 2.4 0 0 1 0 3.4L17 19" />
                            <path
                                d="M9.586 5.586A2 2 0 0 0 8.172 5H3a1 1 0 0 0-1 1v5.172a2 2 0 0 0 .586 1.414L8.29 18.29a2.426 2.426 0 0 0 3.42 0l3.58-3.58a2.426 2.426 0 0 0 0-3.42z"
                            />
                            <circle cx="6.5" cy="9.5" r=".5" fill="currentColor" />
                        </svg>
                        Subkategori
                    </x-admin.side-link>
                </li>
            </ul>
        </nav>
    </div>
</aside>
<div
    x-show="isOpen"
    x-transition:enter="transition-opacity duration-300 ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity duration-200 ease-in"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-30 bg-black/75 lg:hidden"
    @click="isOpen = false"
    x-cloak
></div>
