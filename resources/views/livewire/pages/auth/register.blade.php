<?php

use App\Livewire\Forms\RegisterForm;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    public RegisterForm $form;

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->form->validate();

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::error('An unexpected error occurred when sending email verification to a newly registered user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $user->assignRole('user');

        Auth::login($user);

        session()->flash('success', 'Selamat datang di ' . config('app.name') . ', selamat berbelanja!');

        $this->redirect(route('home', absolute: false), navigate: true);
    }
}; ?>

@section('title', 'Daftar')

<div class="w-full">
    <h1 class="mb-3 text-4xl font-bold text-black">Daftar</h1>
    <p class="mb-6 text-base tracking-tight text-black/70">Silakan daftar terlebih dahulu untuk mulai berbelanja.</p>
    <form wire:submit="register">
        <div>
            <x-form.input-label for="name" value="Nama" />
            <x-form.input
                wire:model.lazy="form.name"
                id="name"
                class="mt-1 block w-full"
                type="text"
                name="name"
                required
                autofocus
                autocomplete="name"
                placeholder="John Doe"
                :hasError="$errors->has('form.name')"
            />
            <x-form.input-error :messages="$errors->get('form.name')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-form.input-label for="email" value="Email" />
            <x-form.input
                wire:model.lazy="form.email"
                id="email"
                class="mt-1 block w-full"
                type="email"
                name="email"
                required
                autocomplete="username"
                placeholder="johndoe@email.com"
                :hasError="$errors->has('form.email')"
            />
            <x-form.input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>
        <div
            x-data="{
                show: false,
                password: '',
                showPassword: false,

                validPasswordLength() {
                    return this.password.length >= 8
                },

                validPasswordHasUpperAndLowerCase() {
                    return /[a-z]/.test(this.password) && /[A-Z]/.test(this.password)
                },

                validPasswordHasNumber() {
                    return /\d/.test(this.password)
                },
            }"
            class="mt-4"
        >
            <div
                x-show="show"
                class="mb-4 rounded-lg border border-neutral-300 bg-white p-4 text-sm"
                role="alert"
                tabindex="-1"
                aria-labelledby="password-requirement"
                x-cloak
            >
                <p id="password-requirement" class="text-sm tracking-tight text-black">
                    Pastikan password akun Anda sudah memenuhi syarat berikut:
                </p>
                <ul class="mt-2 space-y-1">
                    <li
                        class="inline-flex items-center gap-x-2 text-sm tracking-tight"
                        :class="{
                            'text-red-600' : !validPasswordLength(),
                            'text-teal-600' : validPasswordLength()
                        }"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-4 shrink-0"
                        >
                            <path
                                x-show="! validPasswordLength()"
                                fill-rule="evenodd"
                                d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
                                clip-rule="evenodd"
                                x-cloak
                            />
                            <path
                                x-show="validPasswordLength()"
                                fill-rule="evenodd"
                                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                clip-rule="evenodd"
                                x-cloak
                            />
                        </svg>
                        Minimal 8 karakter
                    </li>
                    <li
                        class="inline-flex items-center gap-x-2 text-sm tracking-tight"
                        :class="{
                            'text-red-600' : !validPasswordHasUpperAndLowerCase(),
                            'text-teal-600' : validPasswordHasUpperAndLowerCase()
                        }"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-4 shrink-0"
                        >
                            <path
                                x-show="! validPasswordHasUpperAndLowerCase()"
                                fill-rule="evenodd"
                                d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
                                clip-rule="evenodd"
                                x-cloak
                            />
                            <path
                                x-show="validPasswordHasUpperAndLowerCase()"
                                fill-rule="evenodd"
                                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                clip-rule="evenodd"
                                x-cloak
                            />
                        </svg>
                        Mengandung huruf besar (A-Z) dan huruf kecil (a-z)
                    </li>
                    <li
                        class="inline-flex items-center gap-x-2 text-sm tracking-tight"
                        :class="{
                            'text-red-600' : !validPasswordHasNumber(),
                            'text-teal-600' : validPasswordHasNumber()
                        }"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-4 shrink-0"
                        >
                            <path
                                x-show="! validPasswordHasNumber()"
                                fill-rule="evenodd"
                                d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-1.72 6.97a.75.75 0 1 0-1.06 1.06L10.94 12l-1.72 1.72a.75.75 0 1 0 1.06 1.06L12 13.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L13.06 12l1.72-1.72a.75.75 0 1 0-1.06-1.06L12 10.94l-1.72-1.72Z"
                                clip-rule="evenodd"
                                x-cloak
                            />
                            <path
                                x-show="validPasswordHasNumber()"
                                fill-rule="evenodd"
                                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                clip-rule="evenodd"
                                x-cloak
                            />
                        </svg>
                        Mengandung setidaknya satu angka (0-9)
                    </li>
                </ul>
            </div>
            <x-form.input-label for="password" value="Password" />
            <div class="relative">
                <x-form.input
                    wire:model.lazy="form.password"
                    id="password"
                    class="mt-1 block w-full pe-12"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    x-model="password"
                    x-on:focus="show = true"
                    x-on:blur="show = false"
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
        <div x-data="{ showPassword: false }" class="mt-4">
            <x-form.input-label for="password_confirmation" value="Konfirmasi Password" />
            <div class="relative">
                <x-form.input
                    wire:model.lazy="form.password_confirmation"
                    id="password_confirmation"
                    class="mt-1 block w-full pe-12"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                    :hasError="$errors->has('form.password_confirmation')"
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
            <x-form.input-error :messages="$errors->get('form.password_confirmation')" class="mt-2" />
        </div>
        <div class="mt-4">
            <div class="flex">
                <x-form.checkbox
                    wire:model.lazy="form.accept_terms_and_conditions"
                    id="accept-terms-and-condition"
                    name="accept-terms-and-condition"
                    required
                    :hasError="$errors->has('form.accept_terms_and_conditions')"
                />
                <label for="accept-terms-and-condition" class="ms-2 text-sm text-black/70">
                    Saya telah membaca dan menyetujui
                    <a
                        href="{{ route('terms-and-conditions') }}"
                        class="font-medium text-black underline transition-colors hover:text-primary"
                        target="_blank"
                    >
                        Syarat dan Ketentuan
                    </a>
                    toko.
                </label>
            </div>
            <x-form.input-error :messages="$errors->get('form.accept_terms_and_conditions')" class="mt-2" />
        </div>
        <div class="mt-8 flex flex-col items-center justify-end gap-y-4">
            <x-common.button type="submit" variant="primary" class="w-full">
                <span wire:loading.remove wire:target="register">Daftar</span>
                <div
                    class="ms-1 inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent"
                    role="status"
                    aria-label="loading"
                    wire:loading
                    wire:target="register"
                ></div>
                <span wire:loading wire:target="register">Sedang Diproses...</span>
            </x-common.button>
            <x-common.button type="button" x-on:click="window.history.back()" variant="secondary" class="w-full">
                Kembali
            </x-common.button>
            <p class="mt-2 text-center text-sm tracking-tight text-black/70">
                Sudah mempunyai akun? Silakan klik
                <a
                    href="{{ route('login') }}"
                    class="font-medium text-black underline transition-colors hover:text-primary"
                    wire:navigate
                >
                    disini
                </a>
                untuk masuk.
            </p>
        </div>
    </form>
</div>
