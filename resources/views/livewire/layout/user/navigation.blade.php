<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public int $cartItemsCount = 0;

    public function mount()
    {
        if (auth()->check()) {
            $this->updateCartItemsCount();
        }
    }

    private function updateCartItemsCount()
    {
        $this->cartItemsCount = auth()->user()->cart
            ? auth()
                ->user()
                ->cart->items->count()
            : 0;
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<header class="sticky top-0 z-10 border-b border-neutral-300 bg-white">
    <nav
        x-data="{
            open: false,
            toggleNav() {
                this.open = ! this.open

                if (this.open) {
                    document.body.style.overflow = 'hidden'
                    document.querySelector('main').style.overflow = 'hidden'
                    document.getElementById('responsive-navbar').style.overflowY =
                        'scroll'
                } else {
                    document.body.style.overflow = ''
                    document.querySelector('main').style.overflow = ''
                    document.getElementById('responsive-navbar').style.overflow = ''
                }
            },
        }"
        class="relative flex items-center justify-between px-4 md:px-6"
    >
        <div class="flex h-14 items-center">
            <a href="{{ route('home') }}" wire:navigate>
                <x-common.application-logo class="block h-9 w-auto fill-current text-primary" />
            </a>
            <ul class="hidden md:-my-px md:ms-8 md:flex md:items-center md:space-x-4 lg:ms-12 lg:space-x-8">
                <li>
                    <x-user.nav-link
                        :href="route('home')"
                        :active="request()->routeIs('home')"
                        class="h-14"
                        wire:navigate
                    >
                        Beranda
                    </x-user.nav-link>
                </li>
            </ul>
        </div>
        <div class="flex items-center space-x-2">
            <button type="button" class="rounded-full p-2 text-black hover:bg-neutral-100">
                <svg
                    class="size-5"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
            </button>
            @auth
                <button type="button" class="relative rounded-full p-2 text-black hover:bg-neutral-100">
                    <svg
                        class="size-5"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                    </svg>
                    <span class="sr-only">notifikasi masuk</span>
                    <div
                        class="absolute -end-2 -top-2 inline-flex size-6 items-center justify-center rounded-full border border-white bg-red-500 text-xs font-semibold text-white"
                    >
                        9+
                    </div>
                </button>
                @can('view own cart')
                    <button
                        x-on:click.prevent.stop="$dispatch('open-offcanvas', 'cart-offcanvas-summary')"
                        type="button"
                        class="relative rounded-full p-2 text-black hover:bg-neutral-100"
                    >
                        <svg
                            class="size-5"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="8" cy="21" r="1" />
                            <circle cx="19" cy="21" r="1" />
                            <path
                                d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"
                            />
                        </svg>
                        <span class="sr-only">total produk di keranjang belanja</span>
                        @if ($cartItemsCount > 0)
                            <div
                                class="absolute -end-2 -top-2 inline-flex size-6 items-center justify-center rounded-full border border-white bg-red-500 text-xs font-semibold text-white"
                            >
                                {{ $cartItemsCount > 9 ? '9+' : $cartItemsCount }}
                            </div>
                        @endif
                    </button>
                    @push('overlays')
                        <x-common.offcanvas name="cart-offcanvas-summary">
                            <x-slot name="title">Ringkasan Keranjang Belanja Saya</x-slot>
                            <livewire:user.cart-item-list />
                        </x-common.offcanvas>
                    @endpush
                @endcan

                <div class="hidden md:flex md:items-center md:ps-2 lg:ps-4">
                    <x-common.dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <x-common.button variant="secondary" class="!px-4">
                                <p
                                    x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                                    x-text="name"
                                    x-on:profile-updated.window="name = $event.detail.name"
                                ></p>
                                <div>
                                    <svg
                                        class="h-4 w-4 fill-current"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                            </x-common.button>
                        </x-slot>
                        <x-slot name="content">
                            @can('access admin page')
                                <x-common.dropdown-link :href="route('admin.dashboard')" wire:navigate>
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
                                    Dashboard Admin
                                </x-common.dropdown-link>
                            @endcan

                            <x-common.dropdown-link :href="route('profile')" wire:navigate>
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
                                    <circle cx="12" cy="8" r="5" />
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                </svg>
                                Profil Saya
                            </x-common.dropdown-link>
                            <x-common.dropdown-link wire:click="logout" class="text-red-500 hover:bg-red-50">
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
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" x2="9" y1="12" y2="12" />
                                </svg>
                                Keluar
                            </x-common.dropdown-link>
                        </x-slot>
                    </x-common.dropdown>
                </div>
            @else
                <div class="hidden space-x-2 md:block">
                    <x-common.button :href="route('register')" variant="secondary" wire:navigate>
                        Registrasi
                    </x-common.button>
                    <x-common.button :href="route('login')" variant="primary" wire:navigate>Masuk</x-common.button>
                </div>
            @endauth
            <div class="-me-2 flex items-center md:hidden">
                <button
                    type="button"
                    class="relative rounded-full p-2 text-black hover:bg-neutral-100"
                    @click="toggleNav()"
                >
                    <svg class="size-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path
                            :class="{ 'hidden': open, 'inline-flex': !open }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                        <path
                            :class="{ 'hidden': !open, 'inline-flex': open }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
        <div
            id="responsive-navbar"
            :class="{ 'block': open, 'hidden': !open }"
            class="absolute right-0 top-full hidden h-[calc(100vh-3.5rem-1px)] w-full bg-white md:hidden"
        >
            <ul class="space-y-1 pb-3 pt-2">
                <li>
                    <x-user.responsive-nav-link
                        :href="route('home')"
                        :active="request()->routeIs('home')"
                        wire:navigate
                    >
                        Beranda
                    </x-user.responsive-nav-link>
                </li>
            </ul>
            @auth
                <div class="border-t border-neutral-300 pb-1 pt-4">
                    <div class="px-4">
                        <p
                            class="text-sm font-medium text-black"
                            x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                            x-text="name"
                            x-on:profile-updated.window="name = $event.detail.name"
                        ></p>
                        <p class="text-sm font-medium text-black/75">{{ auth()->user()->email }}</p>
                    </div>
                    <div class="mt-3 space-y-1">
                        @can('access admin page')
                            <x-user.responsive-nav-link :href="route('admin.dashboard')" wire:navigate>
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
                                Dashboard Admin
                            </x-user.responsive-nav-link>
                        @endcan

                        <x-user.responsive-nav-link :href="route('profile')" wire:navigate>
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
                                <circle cx="12" cy="8" r="5" />
                                <path d="M20 21a8 8 0 0 0-16 0" />
                            </svg>
                            Profil Saya
                        </x-user.responsive-nav-link>
                        <button wire:click="logout" class="w-full text-start">
                            <x-user.responsive-nav-link class="!text-red-500 hover:border-red-300 hover:bg-red-50">
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
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" x2="9" y1="12" y2="12" />
                                </svg>
                                Keluar
                            </x-user.responsive-nav-link>
                        </button>
                    </div>
                </div>
            @else
                <div class="m-4 block space-x-2 md:hidden">
                    <x-common.button :href="route('register')" variant="secondary" wire:navigate>
                        Registrasi
                    </x-common.button>
                    <x-common.button :href="route('login')" variant="primary" wire:navigate>Masuk</x-common.button>
                </div>
            @endauth
        </div>
    </nav>
</header>
