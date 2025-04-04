<?php

use App\Livewire\Forms\ResetPasswordForm;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    public ResetPasswordForm $form;

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->form->token = $token;

        $this->form->email = request()->string('email');

        if ($this->form->token && $this->form->email) {
            $status = Password::broker()->tokenExists(
                User::where('email', $this->form->email)->first(),
                $this->form->token,
            );

            if (! $status) {
                session()->flash(
                    'error',
                    'Token reset password Anda telah kedaluwarsa atau tidak valid, silakan meminta link reset password baru.',
                );

                $this->redirectRoute('home', navigate: true);
            }
        }
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $validated = $this->form->validate();

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset($this->only('email', 'password', 'password_confirmation', 'token'), function (
            $user,
        ) use ($validated) {
            $user
                ->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])
                ->save();

            event(new PasswordReset($user));
        });

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', 'Password akun Anda berhasil di-reset.');

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

@section('title', 'Reset Password')

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
        <h1 class="text-4xl font-bold text-black">Reset Password</h1>
    </div>
    <p class="mb-6 text-base tracking-tight text-black/70">
        Silakan isi formulir di bawah ini untuk mengubah password akun Anda.
    </p>
    <form wire:submit="resetPassword">
        <div>
            <x-form.input-label for="email" value="Email" />
            <x-form.input
                wire:model.lazy="form.email"
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
            <x-form.input-label for="password" value="Password Baru" />
            <div class="relative">
                <x-form.input
                    wire:model.lazy="form.password"
                    id="password"
                    class="mt-1 block w-full pe-12"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Isikan password baru anda disini..."
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
            <x-form.input-label for="password_confirmation" value="Konfirmasi Password Baru" />
            <div class="relative">
                <x-form.input
                    wire:model.lazy="form.password_confirmation"
                    id="password_confirmation"
                    class="mt-1 block w-full pe-12"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Isikan konfirmasi password baru anda disini..."
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
        <div class="mt-8 flex items-center">
            <x-common.button type="submit" variant="primary" class="w-full">
                <span wire:loading.remove wire:target="resetPassword">Reset Password</span>
                <div
                    class="ms-1 inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent"
                    role="status"
                    aria-label="loading"
                    wire:loading
                    wire:target="resetPassword"
                ></div>
                <span wire:loading wire:target="resetPassword">Sedang Diproses...</span>
            </x-common.button>
        </div>
    </form>
</div>
