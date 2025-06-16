<?php

use App\Livewire\Forms\UpdateProfileForm;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new class extends Component {
    public UpdateProfileForm $form;

    #[Locked]
    public Collection $provinces;

    #[Locked]
    public Collection $cities;

    #[Validate]
    public string $password;

    #[Validate]
    public string $passwordConfirmation;

    public function mount(User $user)
    {
        $this->form->setUser($user);

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
     * Update user profile information.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to update the user profile information.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function save()
    {
        $validated = $this->form->validate();

        if ($this->password) {
            $this->validate(
                rules: [
                    'password' => ['required', 'string', Password::defaults(), 'confirmed:passwordConfirmation'],
                    'passwordConfirmation' => ['required', 'string'],
                ],
                messages: [
                    'password.required' => 'Password wajib diisi.',
                    'password.min' => 'Password minimal 8 karakter.',
                    'passwordConfirmation.same' => 'Konfirmasi password tidak cocok.',
                ],
                attributes: [
                    'password' => 'Password baru',
                    'passwordConfirmation' => 'Konfirmasi password baru',
                ],
            );
        }

        try {
            $this->authorize('update', $this->form->user);

            DB::transaction(function () use ($validated) {
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

                if ($this->password) {
                    $this->form->user->password = Hash::make($this->password);

                    $this->form->user->save();
                }
            });

            session()->flash('success', 'Pelanggan ' . $validated['name'] . ' berhasil diubah.');
            return $this->redirectIntended(route('admin.users.index'), navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.users.index'), navigate: true);
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
                    'operation' => 'Admin updating user information',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah informasi profil anda, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.users.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Admin updating user information',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.users.index'), navigate: true);
        }
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow">
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Pribadi Pelanggan</h2>
        </legend>
        <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
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
                    />
                </div>
                <x-form.input-error :messages="$errors->get('form.phone')" class="mt-2" />
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend class="flex w-full border-y border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Alamat Pelanggan</h2>
        </legend>
        <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
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
                />
                <x-form.input-error :messages="$errors->get('form.postalCode')" class="mt-2" />
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend class="flex w-full border-y border-neutral-300 p-4">
            <h2 class="text-lg text-black">Ubah Password Pelanggan</h2>
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
            class="grid grid-cols-1 gap-4 p-4"
        >
            <div
                x-show="show"
                class="rounded-lg border border-neutral-300 bg-white p-4 text-sm"
                role="alert"
                tabindex="-1"
                aria-labelledby="password-requirement"
                x-cloak
            >
                <p id="password-requirement" class="text-sm tracking-tight text-black">
                    Pastikan password akun pelanggan sudah memenuhi syarat berikut:
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
            <div x-data="{ showPassword: false }">
                <x-form.input-label for="password" value="Password baru" />
                <div class="relative">
                    <x-form.input
                        wire:model.lazy="password"
                        id="password"
                        name="password"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full pe-12"
                        placeholder="Password baru..."
                        autocomplete="new-password"
                        x-model="password"
                        x-on:focus="show = true"
                        x-on:blur="show = false"
                        :hasError="$errors->has('password')"
                    />
                    <button
                        type="button"
                        class="absolute end-4 top-1/2 -translate-y-1/2 text-black/70 transition-colors hover:text-black disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:text-black/70"
                        tabindex="-1"
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
                <x-form.input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div x-data="{ showPassword: false }">
                <x-form.input-label for="password-confirmation" value="Konfirmasi password baru" />
                <div class="relative">
                    <x-form.input
                        wire:model.lazy="passwordConfirmation"
                        id="password-confirmation"
                        name="password-confirmation"
                        x-bind:type="showPassword ? 'text' : 'password'"
                        class="mt-1 block w-full pe-12"
                        placeholder="Konfirmasi password baru..."
                        autocomplete="new-password"
                        :hasError="$errors->has('passwordConfirmation')"
                    />
                    <button
                        type="button"
                        class="absolute end-4 top-1/2 -translate-y-1/2 text-black/70 transition-colors hover:text-black disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:text-black/70"
                        tabindex="-1"
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
                <x-form.input-error :messages="$errors->get('passwordConfirmation')" class="mt-2" />
            </div>
        </div>
    </fieldset>
    <div class="flex flex-col justify-end gap-4 p-4 md:flex-row">
        <x-common.button
            :href="route('admin.users.index')"
            variant="secondary"
            wire:loading.class="!pointers-event-none !cursor-not-allowed opacity-50"
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
