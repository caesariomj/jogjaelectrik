<?php

use App\Livewire\Forms\UpdatePasswordForm;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public UpdatePasswordForm $form;

    public bool $isEditing = false;

    public function mount()
    {
        $this->form->setUser(auth()->user());
    }

    public function updatePassword()
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
            return $this->redirectIntended(route('setting'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('setting'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database Error During User Password Alteration', [
                'error_message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah password akun anda, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('setting'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected User Password Alteration Error', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('setting'), navigate: true);
        }
    }
}; ?>

<section>
    <div x-data="{ isEditing: $wire.entangle('isEditing') }">
        <form wire:submit="updatePassword" class="gap-8">
            <fieldset>
                <legend class="flex w-full flex-col pb-4">
                    <h2 class="mb-2 text-xl text-black">Ubah Password Akun</h2>
                    <p class="text-base tracking-tight text-black/70">
                        Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar akun Anda tetap aman.
                    </p>
                </legend>
                <div class="grid grid-cols-1 gap-4 pb-8 pt-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-form.input-label for="current-password" value="Password saat ini" />
                        <x-form.input
                            wire:model.lazy="form.currentPassword"
                            id="current-password"
                            name="current-password"
                            type="password"
                            class="mt-1 block w-full"
                            placeholder="Password saat ini..."
                            autocomplete="current-password"
                            :hasError="$errors->has('form.currentPassword')"
                            x-bind:disabled="!isEditing"
                        />
                        <x-form.input-error :messages="$errors->get('form.currentPassword')" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <x-form.input-label for="password" value="Password baru" />
                        <x-form.input
                            wire:model.lazy="form.password"
                            id="password"
                            name="password"
                            type="password"
                            class="mt-1 block w-full"
                            placeholder="Password baru..."
                            autocomplete="new-password"
                            :hasError="$errors->has('form.password')"
                            x-bind:disabled="!isEditing"
                        />
                        <x-form.input-error :messages="$errors->get('form.password')" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <x-form.input-label for="password-confirmation" value="Konfirmasi password baru" />
                        <x-form.input
                            wire:model.lazy="form.passwordConfirmation"
                            id="password-confirmation"
                            name="password-confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            placeholder="Konfirmasi password baru..."
                            autocomplete="new-password"
                            :hasError="$errors->has('form.passwordConfirmation')"
                            x-bind:disabled="!isEditing"
                        />
                        <x-form.input-error :messages="$errors->get('form.passwordConfirmation')" class="mt-2" />
                    </div>
                </div>
            </fieldset>

            @can('update', $form->user)
                <template x-if="isEditing">
                    <div class="flex flex-col items-center justify-end gap-4 md:flex-row">
                        <x-common.button
                            variant="secondary"
                            class="w-full md:w-fit"
                            x-on:click="isEditing = false"
                            wire:loading.class="opacity-50 !pointers-event-none !cursor-not-allowed hover:!bg-neutral-100"
                            wire:target="updatePassword"
                        >
                            Batal
                        </x-common.button>
                        <x-common.button
                            variant="primary"
                            type="submit"
                            class="w-full md:w-fit"
                            wire:loading.attr="disabled"
                            wire:target="updatePassword"
                        >
                            <span wire:loading.remove wire:target="updatePassword">Simpan</span>
                            <div
                                wire:loading
                                wire:target="updatePassword"
                                class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                                role="status"
                                aria-label="loading"
                            >
                                <span class="sr-only">Sedang diproses...</span>
                            </div>
                            <span wire:loading wire:target="updatePassword">Sedang diproses...</span>
                        </x-common.button>
                    </div>
                </template>
            @endcan
        </form>
        @can('update', $form->user)
            <template x-if="!isEditing">
                <div class="flex items-center justify-end gap-4">
                    <x-common.button variant="secondary" class="w-full md:w-fit" x-on:click="isEditing = true">
                        Ubah
                    </x-common.button>
                </div>
            </template>
        @endcan
    </div>
</section>
