@php
    $ordersCount = \Illuminate\Support\Facades\DB::table('orders')
        ->whereIn('status', ['payment_received', 'processing'])
        ->count();

    $refundsCount = \Illuminate\Support\Facades\DB::table('refunds')
        ->where('status', 'pending')
        ->count();
@endphp

<aside
    class="fixed inset-y-0 start-0 z-[2] h-screen w-64 transform border-e border-neutral-300 bg-white text-black transition-all duration-300 ease-in lg:translate-x-0"
    :class="{
        '-translate-x-full': !isOpen,
        'translate-x-0': isOpen,
        'h-[calc(100vh-40px)] top-[40px]': hasSession,
        'h-screen top-0': !hasSession
    }"
    x-cloak
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
                    <span class="text-xs font-semibold uppercase tracking-tight text-black/50">Pesanan</span>
                </li>
                <li>
                    <x-admin.side-link
                        :href="route('admin.orders.index')"
                        :active="request()->routeIs('admin.orders.*')"
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
                                d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"
                            />
                            <path d="M12 22V12" />
                            <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7" />
                            <path d="m7.5 4.27 9 5.15" />
                        </svg>
                        Pesanan
                        @if ($ordersCount > 0)
                            <span
                                @class([
                                    'ml-auto inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-white text-red-500' => request()->routeIs('admin.orders.*'),
                                    'bg-red-500 text-white' => ! request()->routeIs('admin.orders.*'),
                                ])
                            >
                                {{ $ordersCount }}
                            </span>
                        @endif
                    </x-admin.side-link>
                </li>
                <li>
                    <x-admin.side-link
                        :href="route('admin.refunds.index')"
                        :active="request()->routeIs('admin.refunds.*')"
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
                            <path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17" />
                            <path
                                d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9"
                            />
                            <path d="m2 16 6 6" />
                            <circle cx="16" cy="9" r="2.9" />
                            <circle cx="6" cy="5" r="3" />
                        </svg>
                        Permintaan Refund
                        @if ($refundsCount > 0)
                            <span
                                @class([
                                    'ml-auto inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-white text-red-500' => request()->routeIs('admin.refunds.*'),
                                    'bg-red-500 text-white' => ! request()->routeIs('admin.refunds.*'),
                                ])
                            >
                                {{ $refundsCount }}
                            </span>
                        @endif
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
                <li class="border-t py-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-black/50">Penjualan</span>
                </li>
                <li>
                    <x-admin.side-link
                        :href="route('admin.discounts.index')"
                        :active="request()->routeIs('admin.discounts.*')"
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
                                d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"
                            />
                            <path d="m15 9-6 6" />
                            <path d="M9 9h.01" />
                            <path d="M15 15h.01" />
                        </svg>
                        Diskon
                    </x-admin.side-link>
                </li>
                @can('view reports')
                    <li>
                        <x-admin.side-link
                            :href="route('admin.reports.sales')"
                            :active="request()->routeIs('admin.reports.sales')"
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
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                <path d="M10 9H8" />
                                <path d="M16 13H8" />
                                <path d="M16 17H8" />
                            </svg>
                            Laporan Penjualan
                        </x-admin.side-link>
                    </li>
                @endcan

                <li class="border-t py-2">
                    <span class="text-xs font-semibold uppercase tracking-tight text-black/50">Pengguna</span>
                </li>
                <li>
                    <x-admin.side-link
                        :href="route('admin.users.index')"
                        :active="request()->routeIs('admin.users.*')"
                        wire:navigate
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="size-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"
                            />
                        </svg>
                        Pelanggan
                    </x-admin.side-link>
                </li>
                <li>
                    <x-admin.side-link
                        :href="route('admin.admins.index')"
                        :active="request()->routeIs('admin.admins.*')"
                        wire:navigate
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="size-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"
                            />
                        </svg>
                        Admin
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
    class="fixed inset-0 z-[1] bg-black/75 lg:hidden"
    @click="isOpen = false"
    x-cloak
></div>
