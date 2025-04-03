<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        Session::flash('success', 'Selamat datang di ' . config('app.name') . ', selamat berbelanja!');

        $this->redirectIntended(default: route('home', absolute: false), navigate: true);
    }
}; ?>

@section('title', 'Masuk')

<div class="w-full">
    <div class="mb-3 inline-flex items-center gap-x-2">
        <x-common.button
            :href="route('home')"
            variant="secondary"
            class="!p-2 md:hidden"
            aria-label="Kembali ke halaman utama"
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
        <h1 class="text-4xl font-bold text-black">Masuk</h1>
    </div>
    <p class="mb-6 text-base tracking-tight text-black/70">Silakan masuk terlebih dahulu untuk mulai berbelanja.</p>
    <form wire:submit="login">
        <div>
            <x-form.input-label for="email" value="Email" />
            <x-form.input
                wire:model="form.email"
                id="email"
                class="mt-1 block w-full"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
                placeholder="Isikan email anda disini..."
                :hasError="$errors->has('form.email')"
            />
            <x-form.input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>
        <div x-data="{ showPassword: false }" class="mt-4">
            <x-form.input-label for="password" value="Password" />
            <div class="relative">
                <x-form.input
                    wire:model="form.password"
                    id="password"
                    class="mt-1 block w-full pe-12"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Isikan password anda disini..."
                    :hasError="$errors->has('form.password')"
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
            <x-form.input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>
        <div class="mt-2 flex items-center justify-between">
            <div>
                <x-form.checkbox
                    wire:model.lazy="form.remember"
                    id="remember"
                    name="remember"
                    :hasError="$errors->has('subcategoryFilters')"
                />
                <label for="remember" class="ms-2 text-sm tracking-tight text-black/70">Ingat Saya</label>
            </div>
            @if (Route::has('password.request'))
                <a
                    class="rounded-md text-sm tracking-tight text-black/70 underline transition-colors hover:text-black focus:outline-none"
                    href="{{ route('password.request') }}"
                    wire:navigate
                >
                    Lupa Password?
                </a>
            @endif
        </div>
        <div class="mt-8 flex flex-col items-center justify-end gap-y-4">
            <x-common.button type="submit" variant="primary" class="w-full">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <div
                    class="ms-1 inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent"
                    role="status"
                    aria-label="loading"
                    wire:loading
                    wire:target="login"
                ></div>
                <span wire:loading wire:target="login">Sedang Diproses...</span>
            </x-common.button>
            <p class="text-center text-sm tracking-tight text-black/70">
                Belum mempunyai akun? Silakan klik
                <a href="{{ route('register') }}" class="font-medium text-black underline" wire:navigate>disini</a>
                untuk membuat akun baru.
            </p>
        </div>
    </form>
</div>
