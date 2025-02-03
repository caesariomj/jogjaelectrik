<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<header class="border-b border-neutral-300 bg-white">
    <nav class="relative flex h-14 items-center justify-between bg-white px-4 md:px-6 lg:justify-end">
        <button @click="toggleSidebar" class="relative rounded-full p-2 text-black hover:bg-neutral-100 lg:hidden">
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
                <line x1="4" x2="20" y1="12" y2="12" />
                <line x1="4" x2="20" y1="6" y2="6" />
                <line x1="4" x2="20" y1="18" y2="18" />
            </svg>
        </button>
        <div class="flex items-center space-x-2">
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
            <div class="flex items-center ps-4">
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
                        <x-common.dropdown-link :href="route('admin.profile')" wire:navigate>
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
                        <x-common.dropdown-link :href="route('admin.setting')" wire:navigate>
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
        </div>
    </nav>
</header>
