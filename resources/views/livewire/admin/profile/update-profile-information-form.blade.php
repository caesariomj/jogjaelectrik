<?php

use App\Livewire\Forms\UpdateProfileForm;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public UpdateProfileForm $form;

    public bool $isEditing = false;

    public function mount()
    {
        $this->form->setUser(auth()->user());
    }

    /**
     * Update the admin profile information.
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
                if ($validated['phone'] !== '') {
                    $encryptedPhoneNumber = Crypt::encryptString(ltrim($validated['phone'], '0'));
                }

                $this->form->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone_number' => isset($encryptedPhoneNumber) ? $encryptedPhoneNumber : null,
                ]);
            });

            session()->flash('success', 'Informasi profil Anda berhasil diubah.');
            return $this->redirectIntended(route('admin.profile'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.profile'), navigate: true);
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
                    'operation' => 'Admin updates his profile information',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah informasi profil anda, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.profile'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Admin updates his profile information',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.profile'), navigate: true);
        }
    }
}; ?>

<section class="rounded-xl border border-neutral-300 bg-white shadow">
    <div x-data="{ isEditing: $wire.entangle('isEditing') }">
        <form wire:submit="save">
            <fieldset>
                <legend class="flex w-full border-b border-neutral-300 p-4">
                    <h2 class="text-lg text-black">Informasi Pribadi</h2>
                </legend>
                <div class="p-4">
                    <div class="flex flex-col justify-between gap-4 md:flex-row">
                        <div class="w-full md:w-1/2">
                            <x-form.input-label for="name" value="Nama Lengkap" />
                            <x-form.input
                                wire:model.lazy="form.name"
                                id="name"
                                name="name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                                autocomplete="name"
                                :hasError="$errors->has('form.name')"
                                x-bind:disabled="!isEditing"
                            />
                            <x-form.input-error class="mt-2" :messages="$errors->get('form.name')" />
                        </div>
                        <div class="w-full md:w-1/2">
                            <x-form.input-label for="email" value="Email" />
                            <x-form.input
                                wire:model.lazy="form.email"
                                id="email"
                                name="email"
                                type="email"
                                class="mt-1 block w-full"
                                required
                                autocomplete="username"
                                :hasError="$errors->has('form.email')"
                                x-bind:disabled="!isEditing"
                            />
                            <x-form.input-error class="mt-2" :messages="$errors->get('form.email')" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-form.input-label for="phone" value="Nomor Telefon" class="mb-1" />
                        <div class="relative">
                            <div class="absolute left-0 top-1/2 flex -translate-y-1/2 items-center pl-4">
                                <span
                                    aria-hidden="true"
                                    class="me-4 flex h-4 w-6 flex-col overflow-hidden rounded-sm border border-neutral-300"
                                >
                                    <div class="h-1/2 w-full bg-red-600"></div>
                                    <div class="h-1/2 w-full bg-white"></div>
                                </span>
                                <span
                                    aria-label="Kode Negara Indonesia"
                                    class="mb-[1px] border-l border-neutral-300 px-2 text-sm text-black"
                                >
                                    +62
                                </span>
                            </div>
                            <x-form.input
                                wire:model.lazy.lazy="form.phone"
                                id="phone"
                                class="block w-full ps-24"
                                type="tel"
                                name="phone"
                                placeholder="8XX-XXXX-XXXX"
                                minlength="10"
                                maxlength="15"
                                inputmode="numeric"
                                autocomplete="tel-national"
                                x-on:input="$el.value = $el.value.replace(/^0+/, '')"
                                x-mask="999-9999-9999"
                                :hasError="$errors->has('form.phone')"
                                x-bind:disabled="!isEditing"
                            />
                        </div>
                        <x-form.input-error :messages="$errors->get('form.phone')" class="mt-2" />
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
