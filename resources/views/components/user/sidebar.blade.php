@php
    $ordersCount = \Illuminate\Support\Facades\DB::table('orders')
        ->where('user_id', auth()->id())
        ->whereIn('status', ['waiting_payment', 'shipping'])
        ->count();
@endphp

<aside
    class="hidden md:sticky md:top-20 md:block md:h-full md:w-64 md:shrink-0 md:rounded md:border md:border-neutral-300 md:p-4 md:shadow-md"
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
                            @class([
                                'ml-auto inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-white text-red-500' => request()->routeIs('orders.*'),
                                'bg-red-500 text-white' => ! request()->routeIs('orders.*'),
                            ])
                        >
                            {{ $ordersCount }}
                        </span>
                    @endif
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
