<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('home', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        session()->flash('status', 'Link verifikasi baru telah dikirim ke alamat email Anda.');
    }
}; ?>

@section('title', 'Verifikasi Email')

<div class="w-full">
    <div class="mb-3 inline-flex items-center gap-x-2">
        <x-common.button
            :href="route('home')"
            variant="secondary"
            class="!p-2 md:hidden"
            aria-label="Kembali ke halaman sebelumnya"
            wire:navigate
        >
            <svg
                class="size-4 shrink-0"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.8"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
        </x-common.button>
        <h1 class="text-4xl font-bold text-black">Verifikasi Email</h1>
    </div>
    <p class="mb-6 text-base tracking-tight text-black/70">
        Terima kasih telah mendaftar! Sebelum mulai berbelanja, Anda perlu memverifikasi alamat email Anda dengan
        mengklik tautan yang baru saja kami kirimkan ke email Anda. Jika Anda tidak menerima email tersebut, Anda dapat
        mengklik tombol dibawah ini untuk mengirimkan ulang link verifikasi ke email Anda.
    </p>

    @if (session('status'))
        <div
            class="mt-3 flex items-center gap-x-2 rounded-lg border border-teal-200 bg-teal-100 p-4 text-sm font-medium tracking-tight text-teal-800"
            role="alert"
            tabindex="-1"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="currentColor"
                class="size-4 shrink-0"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                    clip-rule="evenodd"
                />
            </svg>
            {{ session('status') }}
        </div>
    @endif

    <div class="mt-8 flex flex-col items-center gap-y-4">
        <x-common.button variant="primary" class="w-full" wire:click="sendVerification">
            <span wire:loading.remove wire:target="sendVerification">Kirim ulang email verifikasi</span>
            <div
                class="ms-1 inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent"
                role="status"
                aria-label="loading"
                wire:loading
                wire:target="sendVerification"
            ></div>
            <span wire:loading wire:target="sendVerification">Sedang Diproses...</span>
        </x-common.button>
        <x-common.button :href="route('home')" variant="secondary" class="w-full" wire:navigate>
            Kembali
        </x-common.button>
    </div>
</div>
