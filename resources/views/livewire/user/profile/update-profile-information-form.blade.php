<?php

use App\Livewire\Forms\UpdateProfileForm;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    public UpdateProfileForm $form;

    public bool $isEditing = false;

    #[Locked]
    public Collection $provinces;

    #[Locked]
    public Collection $cities;

    public function mount(): void
    {
        $this->form->setUser(auth()->user());

        $this->provinces = DB::table('provinces')
            ->select('id as value', 'name as label')
            ->get();

        $this->cities = $this->form->province
            ? DB::table('cities')
                ->select('id as value', 'name as label')
                ->where('province_id', $this->form->province)
                ->get()
            : collect();
    }

    /**
     * Lazy loading that displays the user update profile skeleton.
     */
    public function placeholder(): View
    {
        return view('components.skeleton.user-update-profile');
    }

    /**
     * Set city and province value when the combobox component change.
     */
    public function handleComboboxChange($value, $comboboxInstanceName): void
    {
        if ($comboboxInstanceName == 'provinsi') {
            $this->form->province = $value;
            $this->form->city = null;

            $this->cities = DB::table('cities')
                ->select('id as value', 'name as label')
                ->where('province_id', $this->form->province)
                ->get();
        } elseif ($comboboxInstanceName == 'kabupaten/kota') {
            $this->form->city = $value;

            $this->cities = DB::table('cities')
                ->select('id as value', 'name as label')
                ->where('province_id', $this->form->province)
                ->get();
        }
    }

    /**
     * Reset / delete user input after pressing cancel button.
     */
    public function resetForm(): void
    {
        $this->form->role = $this->form->originalRole;
        $this->form->name = $this->form->originalName;
        $this->form->email = $this->form->originalEmail;
        $this->form->phone = $this->form->originalPhone;
        $this->form->province = $this->form->originalProvince;
        $this->form->city = $this->form->originalCity;
        $this->form->address = $this->form->originalAddress;
        $this->form->postalCode = $this->form->originalPostalCode;

        $this->form->resetErrorBag();
    }

    /**
     * Update user profile information.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to update the user profile information.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function updateProfileInformation()
    {
        if (! $this->isEditing) {
            return;
        }

        $validated = $this->form->validate();

        try {
            $this->authorize('update', $this->form->user);

            DB::transaction(function () use ($validated) {
                $originalEmail = auth()->user()->email;

                $encryptedPhoneNumber = Crypt::encryptString(ltrim($validated['phone'], '0'));
                $encryptedAddress = Crypt::encryptString($validated['address']);
                $encryptedPostalCode = Crypt::encryptString($validated['postalCode']);

                $this->form->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone_number' => $encryptedPhoneNumber,
                    'city_id' => (int) $validated['city'],
                    'address' => $encryptedAddress,
                    'postal_code' => $encryptedPostalCode,
                ]);

                if ($originalEmail !== $validated['email']) {
                    $this->form->user->email_verified_at = null;

                    $this->form->user->save();
                }
            });

            session()->flash('success', 'Informasi profil Anda berhasil diubah.');
            return $this->redirectIntended(route('profile'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('profile'), navigate: true);
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
                    'operation' => 'User updating their profile information',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah informasi profil anda, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('profile'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'User updating their profile information',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('profile'), navigate: true);
        }
    }

    /**
     * Send email verification to user.
     */
    public function sendVerification()
    {
        if ($this->form->user->hasVerifiedEmail()) {
            $this->redirectIntended(url()->previous(), navigate: true);

            return;
        }

        $this->form->user->sendEmailVerificationNotification();

        $this->dispatch('verification-status-sent');
    }
}; ?>

<div>
    <section x-data="{ isEditing: $wire.entangle('isEditing') }">
        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
            <div class="pointer-events-auto mb-4">
                <div
                    class="rounded-md border border-yellow-400 bg-yellow-50 p-4 shadow-md"
                    role="alert"
                    aria-live="polite"
                    aria-atomic="true"
                >
                    <div class="flex items-center" role="presentation">
                        <div class="flex-shrink-0" aria-hidden="true">
                            <svg class="size-4 text-yellow-800" viewBox="0 0 20 20" fill="currentColor">
                                <title>Ikon notifikasi informasi</title>
                                <path
                                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                                />
                            </svg>
                        </div>
                        <div
                            x-data="{ show: false }"
                            x-on:verification-status-sent.window="show = true"
                            class="ml-3 flex flex-col items-start gap-2"
                        >
                            <p role="heading" aria-level="2" class="text-sm tracking-tight text-yellow-800">
                                <strong>Perhatian! Akun Anda belum ter-aktivasi.</strong>
                                Silakan aktivasi akun Anda terlebih dahulu sebelum mulai berbelanja. Cek email Anda
                                untuk tautan aktivasi atau
                                <button type="button" class="font-bold underline" wire:click.prevent="sendVerification">
                                    klik di sini
                                </button>
                                untuk mengirim ulang.
                            </p>
                            <span
                                x-show="show"
                                x-transition.opacity
                                class="text-sm font-medium tracking-tight text-green-600"
                            >
                                Tautan verifikasi baru telah dikirim ke alamat email Anda.
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit="updateProfileInformation" class="gap-8">
            <fieldset>
                <legend class="flex w-full flex-col pb-4">
                    <h2 class="mb-2 text-xl text-black">Informasi Pribadi</h2>
                    <p class="text-base tracking-tight text-black/70">
                        Masukkan informasi pribadi anda dengan lengkap dan benar agar pesanan Anda dapat segera kami
                        proses.
                    </p>
                </legend>
                <div class="grid grid-cols-1 gap-4 pb-8 pt-4 md:grid-cols-2">
                    <div class="md:col-span-2">
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
                    <div>
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
                    <div>
                        <x-form.input-label for="phone" value="Nomor Telefon" class="mb-1" />
                        <div class="relative">
                            <div
                                class="absolute left-0 top-1/2 flex -translate-y-1/2 items-center border-r border-r-neutral-300 pl-4"
                            >
                                <span
                                    aria-hidden="true"
                                    class="me-4 flex h-4 w-6 flex-col overflow-hidden rounded-sm border border-neutral-300"
                                >
                                    <div class="h-1/2 w-full bg-red-600"></div>
                                    <div class="h-1/2 w-full bg-white"></div>
                                </span>
                            </div>
                            <x-form.input
                                wire:model.lazy="form.phone"
                                id="phone"
                                class="block w-full ps-16"
                                type="tel"
                                name="phone"
                                placeholder="08XX-XXXX-XXXX"
                                minlength="10"
                                maxlength="15"
                                inputmode="numeric"
                                autocomplete="tel-national"
                                x-mask="0999-9999-9999"
                                required
                                :hasError="$errors->has('form.phone')"
                                x-bind:disabled="!isEditing"
                            />
                        </div>
                        <x-form.input-error :messages="$errors->get('form.phone')" class="mt-2" />
                    </div>
                </div>
            </fieldset>
            <fieldset>
                <legend class="flex w-full flex-col border-t border-neutral-300 py-4">
                    <h2 class="mb-2 text-xl text-black">Informasi Pengiriman</h2>
                    <p class="text-base tracking-tight text-black/70">
                        Masukkan alamat pengiriman anda dengan lengkap dan benar agar pesanan Anda dapat segera kami
                        proses.
                    </p>
                </legend>
                <div class="grid grid-cols-1 gap-4 pb-8 pt-4 md:grid-cols-2">
                    <template x-if="isEditing">
                        <div class="flex w-full flex-col gap-4 md:col-span-2 md:flex-row">
                            <div class="w-full md:w-1/2">
                                <p class="pointer-events-none mb-1 block text-sm font-medium tracking-tight text-black">
                                    Pilih Provinsi
                                    <span class="text-red-500">*</span>
                                </p>
                                <x-form.combobox
                                    :options="$this->provinces"
                                    :selectedOption="$form->province ?? null"
                                    name="provinsi"
                                    id="select-province"
                                    wire:ignore.self
                                    x-bind:disabled="!isEditing"
                                />
                                <x-form.input-error :messages="$errors->get('form.province')" class="mt-2" />
                            </div>
                            <div class="w-full md:w-1/2">
                                <p class="pointer-events-none mb-1 block text-sm font-medium tracking-tight text-black">
                                    Pilih Kabupaten/Kota
                                    <span class="text-red-500">*</span>
                                </p>
                                @if (! $form->province)
                                    <button
                                        type="button"
                                        class="inline-flex w-full items-center justify-between gap-2 rounded-md border border-neutral-300 bg-white px-4 py-3 text-sm font-medium tracking-tight text-black transition hover:opacity-75 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black disabled:cursor-not-allowed disabled:opacity-50"
                                        disabled
                                    >
                                        <span
                                            wire:loading.remove
                                            wire:target="handleComboboxChange"
                                            class="text-sm font-medium capitalize tracking-tight text-black"
                                        >
                                            Silakan pilih provinsi anda terlebih dahulu
                                        </span>
                                        <span
                                            wire:loading
                                            wire:target="handleComboboxChange"
                                            class="text-sm font-medium capitalize tracking-tight text-black"
                                        >
                                            Sedang diproses...
                                        </span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                            class="size-5"
                                            aria-hidden="true"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                @else
                                    <div wire:key="select-city-container-{{ $form->province }}">
                                        <x-form.combobox
                                            :options="$cities"
                                            :selectedOption="$form->city ?? null"
                                            name="kabupaten/kota"
                                            id="select-city"
                                            wire:ignore.self
                                        />
                                    </div>
                                @endif
                                <x-form.input-error :messages="$errors->get('form.city')" class="mt-2" />
                            </div>
                        </div>
                    </template>
                    <template x-if="!isEditing">
                        <div class="flex w-full flex-col gap-4 md:col-span-2 md:flex-row">
                            <div class="w-full md:w-1/2">
                                <p class="pointer-events-none mb-1 block text-sm font-medium tracking-tight text-black">
                                    Pilih Provinsi
                                    <span class="text-red-500">*</span>
                                </p>
                                <div
                                    class="inline-flex w-full cursor-not-allowed items-center justify-between gap-2 rounded-md border border-neutral-300 bg-white px-4 py-3 text-sm font-medium tracking-tight text-black opacity-50 shadow-sm transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black"
                                >
                                    <span class="text-sm font-normal capitalize text-black">
                                        {{ auth()->user()->city ? auth()->user()->city->province->name : 'Silakan Pilih Provinsi' }}
                                    </span>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        class="size-5"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2">
                                <p class="pointer-events-none mb-1 block text-sm font-medium tracking-tight text-black">
                                    Pilih Kabupaten/Kota
                                    <span class="text-red-500">*</span>
                                </p>
                                <div
                                    class="inline-flex w-full cursor-not-allowed items-center justify-between gap-2 rounded-md border border-neutral-300 bg-white px-4 py-3 text-sm font-medium tracking-tight text-black opacity-50 shadow-sm transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black"
                                >
                                    <span class="text-sm font-normal capitalize text-black">
                                        {{ auth()->user()->city ? auth()->user()->city->name : 'Silakan Pilih Kabupaten/Kota' }}
                                    </span>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        class="size-5"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div class="md:col-span-2">
                        <x-form.input-label for="address" value="Alamat Lengkap" class="mb-1" />
                        <x-form.textarea
                            wire:model.lazy.lazy="form.address"
                            id="address"
                            name="address"
                            rows="5"
                            placeholder="Isikan alamat lengkap anda di sini..."
                            minlength="10"
                            maxlength="1000"
                            autocomplete="shipping street-address"
                            required
                            :hasError="$errors->has('form.address')"
                            x-bind:disabled="!isEditing"
                        ></x-form.textarea>
                        <x-form.input-error :messages="$errors->get('form.address')" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <x-form.input-label for="postal-code" value="Kode Pos" class="mb-1" />
                        <x-form.input
                            wire:model.lazy="form.postalCode"
                            id="postal-code"
                            class="block w-full [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            type="number"
                            name="postal-code"
                            placeholder="Isikan kode pos anda disini..."
                            minlength="5"
                            maxlength="5"
                            autocomplete="shipping postal-code"
                            required
                            x-mask="99999"
                            :hasError="$errors->has('form.postalCode')"
                            x-bind:disabled="!isEditing"
                        />
                        <x-form.input-error :messages="$errors->get('form.postalCode')" class="mt-2" />
                    </div>
                </div>
            </fieldset>
            @can('update', $form->user)
                <template x-if="isEditing">
                    <div class="flex flex-col items-center justify-end gap-4 md:flex-row">
                        <x-common.button
                            variant="secondary"
                            class="w-full md:w-fit"
                            x-on:click="isEditing = false; $wire.resetForm()"
                            wire:loading.class="opacity-50 !pointers-event-none !cursor-not-allowed hover:!bg-neutral-100"
                            wire:target="updateProfileInformation"
                        >
                            Batal
                        </x-common.button>
                        <x-common.button
                            variant="primary"
                            type="submit"
                            class="w-full md:w-fit"
                            wire:loading.attr="disabled"
                            wire:target="updateProfileInformation"
                        >
                            <span wire:loading.remove wire:target="updateProfileInformation">Simpan</span>
                            <div
                                wire:loading
                                wire:target="updateProfileInformation"
                                class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                                role="status"
                                aria-label="loading"
                            >
                                <span class="sr-only">Sedang diproses...</span>
                            </div>
                            <span wire:loading wire:target="updateProfileInformation">Sedang diproses...</span>
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
    </section>
</div>
