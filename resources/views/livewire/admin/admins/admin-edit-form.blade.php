<?php

use App\Livewire\Forms\AdminForm;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public AdminForm $form;

    public function mount(User $admin)
    {
        $this->form->setUser($admin);
    }

    public function save()
    {
        $validated = $this->form->validate();

        try {
            $this->authorize('create', User::class);

            DB::transaction(function () use ($validated) {
                $this->form->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                ]);

                if ($validated['password']) {
                    $this->form->user->update([
                        'password' => Hash::make($validated['password']),
                    ]);
                }
            });

            session()->flash('success', 'Admin ' . $validated['name'] . ' berhasil ditambahkan.');
            $this->redirectRoute('admin.admins.index', navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.admins.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database error during admin creating user: ' . $e->getMessage());

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menambahkan admin baru, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.admins.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected admin creating user error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.admins.index'), navigate: true);
        }
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow">
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-xl text-black">Informasi Akun</h2>
        </legend>
        <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-form.input-label for="name" value="Nama" />
                <x-form.input
                    wire:model.lazy="form.name"
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="Isikan nama admin disini..."
                    autocomplete="name"
                    required
                    autofocus
                    :hasError="$errors->has('form.name')"
                />
                <x-form.input-error class="mt-2" :messages="$errors->get('form.name')" />
            </div>
            <div class="md:col-span-2">
                <x-form.input-label for="email" value="Email" />
                <x-form.input
                    wire:model.lazy="form.email"
                    id="email"
                    name="email"
                    type="email"
                    class="mt-1 block w-full"
                    placeholder="Isikan email admin disini..."
                    autocomplete="email"
                    required
                    :hasError="$errors->has('form.email')"
                />
                <x-form.input-error class="mt-2" :messages="$errors->get('form.email')" />
            </div>
            <div x-data="{ showPassword: false }">
                <x-form.input-label for="password" value="Password Admin" />
                <div class="relative">
                    <x-form.input
                        wire:model.lazy="form.password"
                        id="password"
                        name="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full pe-12"
                        placeholder="Isikan password admin disini..."
                        autocomplete="new-password"
                        :hasError="$errors->has('form.password')"
                    />
                    <button
                        type="button"
                        class="absolute end-4 top-1/2 -translate-y-1/2 text-black/70 transition-colors hover:text-black"
                        x-on:click="showPassword = !showPassword"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="size-5 shrink-0"
                            x-show="!showPassword"
                            x-cloak
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                            />
                        </svg>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="size-5 shrink-0"
                            x-show="showPassword"
                            x-cloak
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                            />
                        </svg>
                    </button>
                </div>
                <x-form.input-error :messages="$errors->get('form.password')" class="mt-2" />
            </div>
            <div x-data="{ showPassword: false }">
                <x-form.input-label for="password-confirmation" value="Konfirmasi Password Admin" />
                <div class="relative">
                    <x-form.input
                        wire:model.lazy="form.passwordConfirmation"
                        id="password-confirmation"
                        name="password-confirmation"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full"
                        placeholder="Isikan konfirmasi password admin disini..."
                        autocomplete="new-password"
                        :hasError="$errors->has('form.passwordConfirmation')"
                    />
                    <button
                        type="button"
                        class="absolute end-4 top-1/2 -translate-y-1/2 text-black/70 transition-colors hover:text-black"
                        x-on:click="showPassword = !showPassword"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="size-5 shrink-0"
                            x-show="!showPassword"
                            x-cloak
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                            />
                        </svg>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="size-5 shrink-0"
                            x-show="showPassword"
                            x-cloak
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"
                            />
                        </svg>
                    </button>
                </div>
                <x-form.input-error :messages="$errors->get('form.passwordConfirmation')" class="mt-2" />
            </div>
        </div>
    </fieldset>
    <div class="flex flex-col justify-end gap-4 p-4 md:flex-row">
        <x-common.button
            :href="route('admin.admins.index')"
            variant="secondary"
            wire:loading.class="!pointers-event-none !cursor-wait opacity-50"
            wire:target="save"
            wire:navigate
        >
            Batal
        </x-common.button>
        <x-common.button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Simpan</span>
            <span wire:loading.flex wire:target="save" class="items-center gap-x-2">
                <div
                    class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                    role="status"
                    aria-label="loading"
                >
                    <span class="sr-only">Sedang diproses...</span>
                </div>
                Sedang diproses...
            </span>
        </x-common.button>
    </div>
</form>
