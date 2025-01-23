<?php

use App\Livewire\Forms\UpdateProfileForm;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public UpdateProfileForm $form;

    public bool $isEditing = false;

    public Collection $provinces;
    public Collection $cities;

    public function mount()
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

    public function handleComboboxChange($value, $comboboxInstanceName)
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
            Log::error('Database Error During User Profile Alteration', [
                'error_message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah informasi profil anda, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('profile'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected User Profile Alteration Error', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('profile'), navigate: true);
        }
    }

    public function sendVerification()
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        session()->flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <div x-data="{ isEditing: $wire.entangle('isEditing') }">
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

                        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                            <div class="mt-2">
                                <div class="inline-flex items-center">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                        class="me-1 size-4 text-red-500"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <p class="text-sm tracking-tight text-red-500">
                                        Email Anda belum diverifikasi,
                                        <button
                                            wire:click.prevent="sendVerification"
                                            class="ms-0.5 text-sm text-black/70 underline transition-colors hover:text-black focus:outline-none"
                                        >
                                            Klik disini untuk mengirim ulang email verifikasi
                                        </button>
                                    </p>
                                </div>

                                @if (session('status') === 'verification-link-sent')
                                    <div class="mt-2 inline-flex items-center">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                            class="me-1 size-4 text-green-600"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        <p class="text-sm font-medium tracking-tight text-green-600">
                                            Link verifikasi baru telah dikirim pada email Anda.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div>
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
                                required
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
                                        <span class="text-sm font-medium capitalize tracking-tight text-black">
                                            Silakan pilih provinsi anda terlebih dahulu
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
                                        {{ $form->city ? auth()->user()->city->province->name : 'Silakan Pilih Provinsi' }}
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
                                        {{ $form->city ? auth()->user()->city->name : 'Silakan Pilih Kabupaten/Kota' }}
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
                            class="block w-full"
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
                            x-on:click="isEditing = false"
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
    </div>
</section>
