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
                    <div class="p-4">
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
                                x-on:click="isEditing = false"
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
