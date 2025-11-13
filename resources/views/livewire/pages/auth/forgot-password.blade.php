<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate(
            rules: [
                'email' => ['required', 'string', 'email'],
            ],
            attributes: [
                'email' => 'Email',
            ],
        );

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink($this->only('email'), function ($user) {
            $user->notify(new \App\Notifications\ResetPassword(Password::createToken($user)));
        });

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', 'Link reset password berhasil dikirim ke email Anda.');
    }
}; ?>

@section('title', 'Lupa Password')

<div class="w-full">
    <h1 class="text-4xl font-bold text-black">Lupa Password</h1>
    <p class="mt-3 text-base tracking-tight text-black/70">
        Lupa password akun Anda? Isikan email akun Anda yang telah terdaftar pada formulir di bawah ini dan kami akan
        mengirimkan link reset password ke email Anda.
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

    <form wire:submit="sendPasswordResetLink" class="mt-6">
        <div>
            <x-form.input-label for="email" value="Email" />
            <x-form.input
                wire:model.lazy="email"
                id="email"
                class="mt-1 block w-full"
                type="email"
                name="email"
                required
                autofocus
                placeholder="Isikan email anda disini..."
                :hasError="$errors->has('email')"
            />
            <x-form.input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div class="mt-8 flex flex-col items-center justify-center gap-4">
            <x-common.button type="submit" variant="primary" class="w-full">
                <span wire:loading.remove wire:target="sendPasswordResetLink">Kirim Link Reset Password</span>
                <div
                    class="ms-1 inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent"
                    role="status"
                    aria-label="loading"
                    wire:loading
                    wire:target="sendPasswordResetLink"
                ></div>
                <span wire:loading wire:target="sendPasswordResetLink">Sedang Diproses...</span>
            </x-common.button>
            <x-common.button type="button" x-on:click="window.history.back()" variant="secondary" class="w-full">
                Kembali
            </x-common.button>
        </div>
    </form>
</div>
