<?php

use App\Livewire\Forms\UpdatePasswordForm;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Volt\Component;

new class extends Component {
    public UpdatePasswordForm $form;

    public bool $isEditing = false;

    public function mount()
    {
        $this->form->setUser(auth()->user());
    }

    /**
     * Lazy loading that displays the table skeleton with dynamic table rows.
     */
    public function placeholder(): View
    {
        return view('components.skeleton.profile');
    }

    /**
     * Reset / delete user input after pressing cancel button.
     */
    public function resetForm(): void
    {
        $this->form->reset('currentPassword', 'password', 'passwordConfirmation');

        $this->form->resetErrorBag();
    }

    /**
     * Update user password.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to update the user password.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function save()
    {
        if (! $this->isEditing) {
            return;
        }

        $validated = $this->form->validate();

        try {
            $this->authorize('update', $this->form->user);

            DB::transaction(function () use ($validated) {
                $this->form->user->update([
                    'password' => Hash::make($validated['password']),
                ]);
            });

            session()->flash('success', 'Password akun Anda berhasil diubah.');
            return $this->redirectIntended(route('admin.setting'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.setting'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database query error occurred', [
                'error_type' => 'QueryException',
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Admin updates his account password',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah password akun Anda, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.setting'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Admin updates his account password',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.setting'), navigate: true);
        }
    }
}; ?>

<div>
    <section class="rounded-xl border border-neutral-300 bg-white shadow-sm">
        <div x-data="{ isEditing: $wire.entangle('isEditing') }">
            <form wire:submit="save" class="gap-8">
                <fieldset>
                    <legend class="flex w-full border-b border-neutral-300 p-4">
                        <h2 class="text-lg text-black">Ubah Password Akun</h2>
                    </legend>
                    <div
                        x-data="{
                            show: false,
                            password: '',

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
                        class="p-4"
                    >
                        <div x-data="{ showPassword: false }">
                            <x-form.input-label for="current-password" value="Password saat ini" />
                            <div class="relative">
                                <x-form.input
                                    wire:model.lazy="form.currentPassword"
                                    id="current-password"
                                    name="current-password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    class="mt-1 block w-full pe-12"
                                    placeholder="Password saat ini..."
                                    autocomplete="current-password"
                                    :hasError="$errors->has('form.currentPassword')"
                                    x-bind:disabled="!isEditing"
                                />
                                <button
                                    type="button"
                                    class="absolute end-4 top-1/2 -translate-y-1/2 text-black/70 transition-colors hover:text-black disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:text-black/70"
                                    tabindex="-1"
                                    x-on:click="showPassword = !showPassword"
                                    x-bind:disabled="!isEditing"
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
                            <x-form.input-error :messages="$errors->get('form.currentPassword')" class="mt-2" />
                        </div>
                        <div
                            x-show="show"
                            class="mt-4 rounded-lg border border-neutral-300 bg-white p-4 text-sm"
                            role="alert"
                            tabindex="-1"
                            aria-labelledby="password-requirement"
                            x-cloak
                        >
                            <p id="password-requirement" class="text-sm tracking-tight text-black">
                                Pastikan password akun Anda sudah memenuhi syarat berikut:
                            </p>
                            <ul class="mt-2 grid grid-cols-1 space-y-1">
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
                        <div class="mt-4 flex flex-col justify-between gap-4 md:flex-row">
                            <div x-data="{ showPassword: false }" class="w-full md:w-1/2">
                                <x-form.input-label for="password" value="Password baru" />
                                <div class="relative">
                                    <x-form.input
                                        wire:model.lazy="form.password"
                                        id="password"
                                        name="password"
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        class="mt-1 block w-full pe-12"
                                        placeholder="Password baru..."
                                        autocomplete="new-password"
                                        x-model="password"
                                        x-on:focus="show = true"
                                        x-on:blur="show = false"
                                        :hasError="$errors->has('form.password')"
                                        x-bind:disabled="!isEditing"
                                    />
                                    <button
                                        type="button"
                                        class="absolute end-4 top-1/2 -translate-y-1/2 text-black/70 transition-colors hover:text-black disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:text-black/70"
                                        tabindex="-1"
                                        x-on:click="showPassword = !showPassword"
                                        x-bind:disabled="!isEditing"
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
                            <div x-data="{ showPassword: false }" class="w-full md:w-1/2">
                                <x-form.input-label for="password-confirmation" value="Konfirmasi password baru" />
                                <div class="relative">
                                    <x-form.input
                                        wire:model.lazy="form.passwordConfirmation"
                                        id="password-confirmation"
                                        name="password-confirmation"
                                        x-bind:type="showPassword ? 'text' : 'password'"
                                        class="mt-1 block w-full pe-12"
                                        placeholder="Konfirmasi password baru..."
                                        autocomplete="new-password"
                                        :hasError="$errors->has('form.passwordConfirmation')"
                                        x-bind:disabled="!isEditing"
                                    />
                                    <button
                                        type="button"
                                        class="absolute end-4 top-1/2 -translate-y-1/2 text-black/70 transition-colors hover:text-black disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:text-black/70"
                                        tabindex="-1"
                                        x-on:click="showPassword = !showPassword"
                                        x-bind:disabled="!isEditing"
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
                                <x-form.input-error
                                    :messages="$errors->get('form.passwordConfirmation')"
                                    class="mt-2"
                                />
                            </div>
                        </div>
                    </div>
                </fieldset>
                @can('update', $form->user)
                    <template x-if="isEditing">
                        <div class="flex flex-col items-center justify-end gap-4 p-4 md:flex-row">
                            <x-common.button
                                variant="secondary"
                                class="w-full md:w-fit"
                                x-on:click="isEditing = false; $wire.resetForm()"
                                wire:loading.class="opacity-50 !pointers-event-none !cursor-not-allowed hover:!bg-neutral-100"
                                wire:target="save"
                            >
                                Batal
                            </x-common.button>
                            <x-common.button
                                variant="primary"
                                type="submit"
                                class="w-full md:w-fit"
                                wire:loading.attr="disabled"
                                wire:target="save"
                            >
                                <span wire:loading.remove wire:target="save">Simpan</span>
                                <div
                                    wire:loading
                                    wire:target="save"
                                    class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                                    role="status"
                                    aria-label="loading"
                                >
                                    <span class="sr-only">Sedang diproses...</span>
                                </div>
                                <span wire:loading wire:target="save">Sedang diproses...</span>
                            </x-common.button>
                        </div>
                    </template>
                @endcan
            </form>
            @can('update', $form->user)
                <template x-if="!isEditing">
                    <div class="flex items-center justify-end gap-4 p-4">
                        <x-common.button variant="secondary" class="w-full md:w-fit" x-on:click="isEditing = true">
                            Ubah
                        </x-common.button>
                    </div>
                </template>
            @endcan
        </div>
    </section>
</div>
