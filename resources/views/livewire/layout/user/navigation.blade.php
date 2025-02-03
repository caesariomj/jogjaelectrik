<?php

use App\Livewire\Actions\Logout;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component {
    public ?string $role = null;
    public int $cartItemsCount = 0;
    public string $search = '';
    public array $searchResults = [
        'categories' => null,
        'subcategories' => null,
        'products' => null,
    ];

    public function mount()
    {
        if (auth()->check()) {
            $this->role = auth()
                ->user()
                ->roles->first()->name;

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

    public function updatedSearch()
    {
        $validated = $this->validate(
            rules: [
                'search' => 'nullable|string|min:1|max:255',
            ],
        );

        $this->search = strip_tags($validated['search']);

        $this->searchResults = [
            'categories' => DB::table('categories')
                ->select('id', 'name', 'slug')
                ->where('name', 'like', '%' . e($this->search) . '%')
                ->orWhereIn('id', function ($query) {
                    $query
                        ->select('category_id')
                        ->from('subcategories')
                        ->where('name', 'like', '%' . e($this->search) . '%');
                })
                ->limit(3)
                ->get(),
            'subcategories' => DB::table('subcategories')
                ->join('categories', 'subcategories.category_id', '=', 'categories.id')
                ->select(
                    'subcategories.id',
                    'subcategories.name',
                    'subcategories.slug',
                    'categories.slug as category_slug',
                )
                ->where('subcategories.name', 'like', '%' . e($this->search) . '%')
                ->orWhere('categories.slug', 'like', '%' . e($this->search) . '%')
                ->limit(3)
                ->get(),
            'products' => Product::with(['images', 'subcategory', 'subcategory.category'])
                ->where('name', 'like', '%' . $this->search . '%')
                ->orWhereHas('subcategory', function ($query) {
                    return $query->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('subcategory.category', function ($query) {
                    return $query->where('name', 'like', '%' . $this->search . '%');
                })
                ->limit(3)
                ->get(),
        ];
    }

    public function redirectToProduct(?string $category = null, ?string $subcategory = null)
    {
        if (! $category && ! $subcategory) {
            return;
        }

        if ($category && $subcategory === null) {
            session()->put('category_filter', $category);
        }

        if ($category && $subcategory) {
            session()->put('category_filter', $category);
            session()->put('subcategory_filter', $subcategory);
        }

        return $this->redirectRoute('products', navigate: true);
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

<header x-data="{ searchBarOpen: false }" class="border-b border-neutral-300 bg-white">
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
                <li>
                    <x-user.nav-link
                        :href="route('products')"
                        :active="request()->routeIs('products*')"
                        class="h-14"
                        wire:navigate
                    >
                        Produk
                    </x-user.nav-link>
                </li>
            </ul>
        </div>
        <div class="flex items-center space-x-2">
            <button
                type="button"
                class="rounded-full p-2 text-black hover:bg-neutral-100"
                x-on:click="searchBarOpen = ! searchBarOpen"
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

                            <x-common.dropdown-link
                                :href="in_array($role, ['admin', 'super_admin']) ? route('admin.profile') : route('profile')"
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
                                    <circle cx="12" cy="8" r="5" />
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                </svg>
                                Profil Saya
                            </x-common.dropdown-link>

                            @can('view own orders')
                                <x-common.dropdown-link :href="route('orders.index')" wire:navigate>
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
                                    Pesanan Saya
                                </x-common.dropdown-link>
                            @endcan

                            @can('view own account')
                                <x-common.dropdown-link
                                    :href="in_array($role, ['admin', 'super_admin']) ? route('admin.setting') : route('setting')"
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
                                </x-common.dropdown-link>
                            @endcan

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
                <li>
                    <x-user.responsive-nav-link
                        :href="route('products')"
                        :active="request()->routeIs('products.*')"
                        wire:navigate
                    >
                        Produk
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

                        <x-user.responsive-nav-link
                            :href="in_array($role, ['admin', 'super_admin']) ? route('admin.profile') : route('profile')"
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
                                <circle cx="12" cy="8" r="5" />
                                <path d="M20 21a8 8 0 0 0-16 0" />
                            </svg>
                            Profil Saya
                        </x-user.responsive-nav-link>

                        @can('view own orders')
                            <x-user.responsive-nav-link :href="route('orders.index')" wire:navigate>
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
                                Pesanan Saya
                            </x-user.responsive-nav-link>
                        @endcan

                        @can('view own account')
                            <x-user.responsive-nav-link
                                :href="in_array($role, ['admin', 'super_admin']) ? route('admin.setting') : route('setting')"
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
                            </x-user.responsive-nav-link>
                        @endcan

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
    <div
        class="absolute top-0 w-full"
        x-show="searchBarOpen"
        x-data="{ search: $wire.entangle('search').live }"
        x-init="
            $watch('searchBarOpen', (value) => {
                if (value) $nextTick(() => $refs.productSearch.focus())
            })

            $watch('search', (value) => {
                if (value.length > 0) {
                    document.body.style.overflow = 'hidden'
                    document.querySelector('main').style.overflow = 'hidden'
                    document.getElementById('search-result-box').style.overflowY = 'scroll'
                } else {
                    document.body.style.overflow = ''
                    document.querySelector('main').style.overflow = ''
                }
            })
        "
        x-transition:enter="transform transition duration-300 ease-out"
        x-transition:enter-start="-translate-y-12"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transform transition duration-300 ease-in"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="-translate-y-12"
        x-cloak
    >
        <div
            class="relative z-10 flex h-[57px] items-center justify-between gap-x-4 border-b border-neutral-300 bg-white px-4 md:px-6"
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
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
            </svg>
            <form action="{{ route('products') }}" method="GET" class="w-full">
                <input
                    type="text"
                    name="q"
                    id="q"
                    class="w-full border-transparent text-base font-medium tracking-tight focus:border-transparent focus:ring-0"
                    placeholder="Cari produk atau kategori berdasarkan nama..."
                    autocomplete="off"
                    x-model="search"
                    x-ref="productSearch"
                    x-on:keydown.enter="$event.target.form.submit"
                />
            </form>
            <button
                type="button"
                class="rounded-full p-2 text-black hover:bg-neutral-100"
                x-on:click="searchBarOpen = ! searchBarOpen; search = ''"
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
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
        <template x-if="search.length > 0">
            <div
                id="search-result-box"
                class="relative z-0 flex h-[calc(100vh-57px)] w-full flex-col gap-4 overflow-y-auto bg-white p-4 md:flex-row md:gap-6 md:p-6"
                x-data="{ searchResultOpen: false }"
                x-init="
                    $nextTick(() => {
                        searchResultOpen = true
                    })
                "
                x-show="searchResultOpen"
                x-transition:enter="transform transition duration-300 ease-out"
                x-transition:enter-start="-translate-y-12 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transform transition duration-300 ease-in"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="-translate-y-12 opacity-0"
                x-cloak
            >
                @if ($searchResults['categories'] || $searchResults['subcategories'])
                    <section
                        class="flex w-full flex-row justify-between gap-6 md:sticky md:top-0 md:w-1/4 md:flex-col md:justify-start"
                    >
                        <div class="w-full">
                            <h2 class="mb-2 !text-lg text-black">Kategori</h2>
                            <ul class="flex flex-col gap-1">
                                @forelse ($searchResults['categories'] as $category)
                                    <li wire:key="{{ $category->id }}">
                                        <button
                                            wire:click="redirectToProduct('{{ $category->slug }}')"
                                            class="text-base font-medium tracking-tight text-black underline transition-colors hover:text-primary"
                                            wire:loading.class="opacity-25 cursor-not-allowed"
                                            wire:target="search"
                                        >
                                            {{ ucwords($category->name) }}
                                        </button>
                                    </li>
                                @empty
                                    <li>
                                        <p class="text-base font-medium tracking-tight text-black">Tidak ditemukan</p>
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="w-full">
                            <h2 class="mb-2 !text-lg text-black">Subkategori</h2>
                            <ul class="flex flex-col gap-1">
                                @forelse ($searchResults['subcategories'] as $subcategory)
                                    <li wire:key="{{ $subcategory->id }}">
                                        <button
                                            wire:click="redirectToProduct('{{ $subcategory->category_slug }}', '{{ $subcategory->slug }}')"
                                            class="text-base font-medium tracking-tight text-black underline transition-colors hover:text-primary"
                                            wire:loading.class="opacity-25 cursor-not-allowed"
                                            wire:target="search"
                                        >
                                            {{ ucwords($subcategory->name) }}
                                        </button>
                                    </li>
                                @empty
                                    <li>
                                        <p class="text-base font-medium tracking-tight text-black">Tidak ditemukan</p>
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </section>
                @endif

                @if ($searchResults['products'])
                    <section class="w-full md:w-3/4">
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 md:gap-6">
                            @forelse ($searchResults['products'] as $product)
                                <x-common.product-card
                                    :product="$product"
                                    wire:key="{{ $product->id }}"
                                    wire:loading.class="opacity-25 cursor-not-allowed"
                                    wire:target="search"
                                />
                            @empty
                                <p class="col-span-2 text-base font-medium tracking-tight text-black md:col-span-3">
                                    Produk dengan nama
                                    <strong>"{{ $search }}"</strong>
                                    tidak ditemukan
                                </p>
                            @endforelse
                        </div>
                    </section>
                @endif
            </div>
        </template>
    </div>
</header>
