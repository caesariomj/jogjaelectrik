@php
    $ordersCount = \Illuminate\Support\Facades\DB::table('orders')
        ->where('user_id', auth()->id())
        ->whereNotIn('status', ['completed', 'failed', 'canceled'])
        ->count();
@endphp

<aside
    class="hidden md:sticky md:top-20 md:block md:h-full md:w-64 md:shrink-0 md:rounded-md md:border md:border-neutral-300 md:p-4 md:shadow-md"
>
    <ul class="space-y-1.5">
        @can('view own account')
            <li>
                <x-user.side-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>
                    <svg
                        class="size-5 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="8" r="5" />
                        <path d="M20 21a8 8 0 0 0-16 0" />
                    </svg>
                    Profil Saya
                </x-user.side-link>
            </li>
        @endcan

        @can('view own orders')
            <li>
                <x-user.side-link
                    :href="route('orders.index')"
                    :active="request()->routeIs('orders.*')"
                    wire:navigate
                >
                    <svg
                        class="size-5 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path
                            d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"
                        />
                        <path d="M12 22V12" />
                        <path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7" />
                        <path d="m7.5 4.27 9 5.15" />
                    </svg>
                    Pesanan Saya
                    @if ($ordersCount > 0)
                        <span
                            class="ml-auto inline-flex items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-medium text-white"
                        >
                            {{ $ordersCount }}
                        </span>
                    @endif
                </x-user.side-link>
            </li>
        @endcan

        @can('view own payments')
            <li>
                <x-user.side-link
                    :href="route('transactions.index')"
                    :active="request()->routeIs('transactions.*')"
                    wire:navigate
                >
                    <svg
                        class="size-5 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"
                        />
                    </svg>
                    Riwayat Transaksi
                </x-user.side-link>
            </li>
        @endcan

        @can('view own account')
            <li>
                <x-user.side-link :href="route('setting')" :active="request()->routeIs('setting')" wire:navigate>
                    <svg
                        class="size-5 shrink-0"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M2 21a8 8 0 0 1 10.434-7.62" />
                        <circle cx="10" cy="8" r="5" />
                        <circle cx="18" cy="18" r="3" />
                        <path d="m19.5 14.3-.4.9" />
                        <path d="m16.9 20.8-.4.9" />
                        <path d="m21.7 19.5-.9-.4" />
                        <path d="m15.2 16.9-.9-.4" />
                        <path d="m21.7 16.5-.9.4" />
                        <path d="m15.2 19.1-.9.4" />
                        <path d="m19.5 21.7-.4-.9" />
                        <path d="m16.9 15.2-.4-.9" />
                    </svg>
                    Pengaturan Akun
                </x-user.side-link>
            </li>
        @endcan
    </ul>
</aside>
