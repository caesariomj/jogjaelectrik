<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (
            ! Auth::guard('web')->validate([
                'email' => Auth::user()->email,
                'password' => $this->password,
            ])
        ) {
            $this->addError('password', 'Kata sandi yang Anda masukkan salah.');

            return;
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('home', absolute: false), navigate: true);
    }
}; ?>

@section('title', 'Konfirmasi Password')

<div>
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
        <h1 class="text-4xl font-bold text-black">Konfirmasi Password</h1>
    </div>
    <p class="mb-6 text-base tracking-tight text-black/70">
        Sebelum mengakses halaman ini, mohon konfirmasi password akun Anda saat ini.
    </p>

    <form wire:submit="confirmPassword">
        <div x-data="{ showPassword: false }">
            <x-form.input-label for="password" value="Password" />
            <div class="relative">
                <x-form.input
                    wire:model="password"
                    id="password"
                    class="mt-1 block w-full"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Isikan password anda disini..."
                />
                <button type="button" class="absolute inset-y-0 end-4" x-on:click="showPassword = ! showPassword">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                        class="size-5 shrink-0 text-black/70"
                    >
                        <path
                            x-show="!showPassword"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                        />
                        <path
                            x-show="!showPassword"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                        />
                        <path
                            x-show="showPassword"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                        />
                    </svg>
                </button>
            </div>
            <x-form.input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="mt-8 flex items-center justify-end">
            <x-common.button type="submit" variant="primary" class="w-full">
                <span wire:loading.remove wire:target="confirmPassword">Konfirmasi Password</span>
                <div
                    class="ms-1 inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent"
                    role="status"
                    aria-label="loading"
                    wire:loading
                    wire:target="confirmPassword"
                ></div>
                <span wire:loading wire:target="confirmPassword">Sedang Diproses...</span>
            </x-common.button>
        </div>
    </form>
</div>
