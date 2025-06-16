<?php

use App\Exceptions\ApiRequestException;
use App\Livewire\Forms\CheckoutForm;
use App\Models\Cart;
use App\Models\City;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\PaymentService;
use App\Services\ShippingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    protected ShippingService $shippingService;

    protected PaymentService $paymentService;

    public CheckoutForm $form;

    #[Locked]
    public Collection $provinces;

    #[Locked]
    public Collection $cities;

    #[Locked]
    public array $supportedCourierExpeditions = [
        [
            'name' => 'jalur nugraha ekakurir',
            'code' => 'jne',
        ],
        [
            'name' => 'pos indonesia',
            'code' => 'pos',
        ],
        [
            'name' => 'titipan kilat',
            'code' => 'tiki',
        ],
    ];

    public array $selectedCourierServices = [];

    public function boot(ShippingService $shippingService, PaymentService $paymentService)
    {
        $this->shippingService = $shippingService;

        $this->paymentService = $paymentService;
    }

    public function mount(Cart $cart)
    {
        $this->form->setCheckoutData($cart);

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

    public function updated($name, $value)
    {
        $this->resetValidation($name);
        $this->resetErrorBag($name);
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

            $this->getSelectedCourierServices();
        }

        $this->selectedCourierServices = [];

        $this->form->shippingCourier = null;
        $this->form->shippingCourierService = null;
        $this->form->shippingCourierServiceTax = 0;
    }

    public function updatedFormShippingCourier()
    {
        $this->selectedCourierServices = [];

        $this->form->shippingCourierService = null;
        $this->form->shippingCourierServiceTax = 0;

        $this->getSelectedCourierServices();
    }

    public function getSelectedCourierServices()
    {
        if (! $this->form->city || ! $this->form->totalWeight || ! $this->form->shippingCourier) {
            return;
        }

        try {
            $result = $this->shippingService->calculateShippingCost(
                $this->form->city,
                $this->form->totalWeight,
                $this->form->shippingCourier,
            );

            if (isset($result['error']) && $result['error']) {
                throw new \Exception($result['message']);
            }

            $this->selectedCourierServices = $result;
        } catch (ApiRequestException $e) {
            Log::error('RajaOngkir API request exception', [
                'error_type' => 'ApiRequestException',
                'message' => $e->getLogMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Fetching courier service data from RajaOngkir API',
                    'component_name' => $this->getName(),
                ],
            ]);

            session('error', $e->getUserMessage());
            return $this->redirectIntended(route('checkout'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Fetching courier service data from RajaOngkir API',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan yang tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('checkout'), navigate: true);
        }
    }

    public function updatedFormShippingCourierService()
    {
        $selectedCourierServiceData = array_filter($this->selectedCourierServices, function ($service) {
            return strtolower($service['service']) === $this->form->shippingCourierService;
        });

        if (empty($selectedCourierServiceData)) {
            $this->form->shippingCourierService = null;

            $this->addError(
                'form.shippingCourierService',
                'Mohon pilih salah satu layanan kurir yang tersedia di atas ini.',
            );
            return;
        }

        $selectedCourierServiceData = reset($selectedCourierServiceData);

        $this->form->shippingCourierServiceTax = $selectedCourierServiceData['cost_value'];
    }

    public function checkout()
    {
        $validated = $this->form->validate();

        if (! in_array($this->form->shippingCourier, array_column($this->supportedCourierExpeditions, 'code'))) {
            $this->addError(
                'form.shippingCourier',
                'Kurir ekspedisi ' . $this->form->shippingCourier . ' tidak didukung.',
            );
            return;
        }

        $selectedCourierServiceData = array_filter($this->selectedCourierServices, function ($service) {
            return strtolower($service['service']) === $this->form->shippingCourierService;
        });

        if (empty($selectedCourierServiceData)) {
            $this->addError(
                'form.shippingCourierService',
                'Mohon pilih salah satu layanan kurir yang tersedia di atas ini.',
            );
            $this->form->shippingCourierService = null;
            return;
        }

        $selectedCourierServiceData = reset($selectedCourierServiceData);

        if (
            $this->form->shippingCourierService !== strtolower($selectedCourierServiceData['service']) ||
            (float) $this->form->shippingCourierServiceTax !== (float) $selectedCourierServiceData['cost_value']
        ) {
            $this->addError(
                'form.shippingCourierService',
                'Layanan kurir ekspedisi yang dipilih tidak sesuai dengan data yang tersedia.',
            );
            return;
        }

        try {
            $this->authorize('create', Order::class);

            $invoiceUrl = null;

            DB::transaction(function () use ($validated, $selectedCourierServiceData, &$invoiceUrl) {
                $encryptedPhoneNumber = Crypt::encryptString(ltrim($validated['phone'], '0'));
                $encryptedAddress = Crypt::encryptString($validated['address']);
                $encryptedPostalCode = Crypt::encryptString($validated['postalCode']);

                $this->form->user->update([
                    'name' => $validated['name'],
                    'phone_number' => $encryptedPhoneNumber,
                    'city_id' => (int) $validated['city'],
                    'address' => $encryptedAddress,
                    'postal_code' => $encryptedPostalCode,
                ]);

                $shippingAddress =
                    $validated['address'] .
                    ', ' .
                    $validated['postalCode'] .
                    ' - ' .
                    $this->form->user->city->name .
                    ', ' .
                    $this->form->user->city->province->name;

                $estimatedShippingDays = $selectedCourierServiceData['etd'];

                if (strpos($estimatedShippingDays, '-') !== false) {
                    [$minDays, $maxDays] = explode('-', $estimatedShippingDays);
                } else {
                    $minDays = $estimatedShippingDays;
                    $maxDays = $estimatedShippingDays;
                }

                $order = $this->form->user->orders()->create([
                    'shipping_address' => Crypt::encryptString($shippingAddress),
                    'shipping_courier' => strtolower(
                        $this->form->shippingCourier . '-' . $this->form->shippingCourierService,
                    ),
                    'estimated_shipping_min_days' => (int) $minDays,
                    'estimated_shipping_max_days' => (int) $maxDays,
                    'note' => $this->form->note !== '' ? $this->form->note : null,
                    'subtotal_amount' => $this->form->totalPrice,
                    'discount_amount' => $this->form->discountAmount > 0 ? -$this->form->discountAmount : 0.0,
                    'shipping_cost_amount' => $this->form->shippingCourierServiceTax,
                    'total_amount' =>
                        (float) $this->form->totalPrice -
                        (float) ($this->form->discountAmount > 0 ? $this->form->discountAmount : 0) +
                        (float) $this->form->shippingCourierServiceTax,
                ]);

                foreach ($this->form->items as $item) {
                    $orderDetail = $order->details()->create([
                        'product_variant_id' => $item->id,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                    ]);

                    ProductVariant::where('id', $item->id)
                        ->first()
                        ->update([
                            'stock' => (int) $item->stock - $orderDetail->quantity,
                        ]);
                }

                if ($this->form->discount) {
                    $order->update([
                        'discount_id' => $this->form->discount->id,
                    ]);
                }

                $invoice = $this->paymentService->createInvoice($order);

                $order->payment()->create([
                    'xendit_invoice_url' => $invoice['url'],
                ]);

                $invoiceUrl = $invoice['url'];

                $this->form->cart->delete();
            });

            return $this->redirect($invoiceUrl);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('checkout'), navigate: true);
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
                    'operation' => 'User trying to checkout',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan dalam proses checkout, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('checkout'), navigate: true);
        } catch (ApiRequestException $e) {
            Log::error('Xendit API request exception', [
                'error_type' => 'ApiRequestException',
                'message' => $e->getLogMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'User trying to checkout',
                    'component_name' => $this->getName(),
                ],
            ]);

            session('error', $e->getUserMessage());
            return $this->redirectIntended(route('checkout'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'User trying to checkout',
                    'component_name' => $this->getName(),
                ],
            ]);

            session('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('checkout'), navigate: true);
        }
    }
}; ?>

<form wire:submit.prevent="checkout" class="flex flex-col gap-8 lg:flex-row">
    <div class="w-full lg:w-2/3">
        <fieldset>
            <legend class="flex w-full flex-col pb-4">
                <h2 class="mb-2 text-xl text-black">Informasi Pribadi</h2>
                <p class="text-base tracking-tight text-black/70">
                    Masukkan informasi pribadi anda dengan lengkap dan benar agar pesanan Anda dapat segera kami proses.
                </p>
            </legend>
            <div class="grid grid-cols-1 gap-4 pb-8 pt-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-form.input-label for="user-name" value="Nama Lengkap" class="mb-1" />
                    <x-form.input
                        wire:model.lazy="form.name"
                        id="user-name"
                        class="block w-full"
                        type="text"
                        name="user-name"
                        placeholder="Isikan nama lengkap anda disini..."
                        minlength="3"
                        maxlength="255"
                        autocomplete="username"
                        autofocus
                        required
                        :hasError="$errors->has('form.name')"
                    />
                    <x-form.input-error :messages="$errors->get('form.name')" class="mt-2" />
                </div>
                <div class="w-full">
                    <div class="mb-1 flex items-center justify-between">
                        <x-form.input-label for="email" value="Alamat Email" />
                        <a
                            href="{{ route('profile') }}"
                            class="text-sm tracking-tight text-black/70 underline transition-colors hover:text-primary"
                            wire:navigate
                        >
                            Ubah alamat email?
                        </a>
                    </div>
                    <x-form.input
                        wire:model.lazy="form.email"
                        id="email"
                        class="block w-full"
                        type="email"
                        name="email"
                        placeholder="Isikan alamat email anda disini..."
                        minlength="10"
                        maxlength="255"
                        autocomplete="email"
                        disabled
                        readonly
                        required
                    />
                    <x-form.input-error :messages="$errors->get('form.email')" class="mt-2" />
                </div>
                <div class="w-full">
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
            <legend class="flex w-full flex-col border-t border-neutral-300 py-4">
                <h2 class="mb-2 text-xl text-black">Informasi Pengiriman</h2>
                <p class="text-base tracking-tight text-black/70">
                    Masukkan alamat pengiriman anda dengan lengkap dan benar serta pilih salah satu kurir ekspedisi yang
                    tersedia agar pesanan Anda dapat segera kami proses.
                </p>
            </legend>
            <div class="grid grid-cols-1 gap-4 pb-8 pt-4 md:grid-cols-2">
                <div class="w-full">
                    <p class="pointer-events-none mb-1 block text-sm font-medium tracking-tight text-black">
                        Pilih Provinsi
                        <span class="text-red-500">*</span>
                    </p>
                    <x-form.combobox
                        :options="$provinces"
                        :selectedOption="$form->province ?? null"
                        name="provinsi"
                        id="select-province"
                        wire:ignore.self
                    />
                    <x-form.input-error :messages="$errors->get('form.province')" class="mt-2" />
                </div>
                <div class="w-full">
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
                <div class="md:col-span-2">
                    <x-form.input-label for="address" value="Alamat Lengkap" class="mb-1" />
                    <x-form.textarea
                        wire:model.lazy="form.address"
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
                        inputmode="numeric"
                        autocomplete="shipping postal-code"
                        required
                        :hasError="$errors->has('form.postalCode')"
                        x-mask="99999"
                    />
                    <x-form.input-error :messages="$errors->get('form.postalCode')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <p class="pointer-events-none mb-1 block text-sm font-medium tracking-tight text-black">
                        Pilih Kurir Ekspedisi
                        <span class="text-red-500">*</span>
                    </p>
                    <ul class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        @foreach ($supportedCourierExpeditions as $expedition)
                            <li class="relative w-full">
                                <x-form.radio
                                    :inputAttributes="
                                        [
                                            'wire:model.lazy' => 'form.shippingCourier',
                                            'id' => 'expedition-' . $expedition['code'],
                                            'name' => 'select-courier-expedition',
                                            'value' => $expedition['code'],
                                            'x-on:input' => 'selected = \'' . $expedition['code'] . '\'',
                                        ]
                                    "
                                    :labelAttributes="
                                        [
                                            'for' => 'expedition-' . $expedition['code'],
                                            'wire:loading.class' => 'opacity-50 !cursor-wait hover:bg-white',
                                            'wire:target' => 'form.shippingCourier, form.city, handleComboboxChange',
                                        ]
                                    "
                                    :hasError="$errors->has('form.shippingCourier')"
                                >
                                    <img
                                        src="{{ asset('images/logos/shipping/' . $expedition['code'] . '.webp') }}"
                                        alt="Logo {{ strtoupper($expedition['code']) }}"
                                        class="h-auto w-12"
                                        loading="lazy"
                                    />
                                    <p class="inline-flex flex-col items-start gap-y-1 text-sm">
                                        <span class="font-semibold tracking-tight text-black">
                                            {{ $expedition['code'] === 'pos' ? 'POSIND' : strtoupper($expedition['code']) }}
                                        </span>
                                        <span class="font-medium tracking-tight text-black/50">
                                            {{ ucwords($expedition['name']) }}
                                        </span>
                                    </p>
                                </x-form.radio>
                                @if ($form->shippingCourier === $expedition['code'])
                                    <svg
                                        class="absolute end-4 top-3 size-5 shrink-0 fill-primary stroke-primary-50"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="m9 12 2 2 4-4" />
                                    </svg>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <x-form.input-error :messages="$errors->get('form.shippingCourier')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <p class="pointer-events-none mb-1 block text-sm font-medium tracking-tight text-black">
                        Pilih Layanan Ekspedisi
                        <span class="text-red-500">*</span>
                    </p>

                    @if (empty($this->selectedCourierServices))
                        <div
                            class="mb-4 flex items-start rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-yellow-800"
                            role="alert"
                            wire:loading.remove
                            wire:target="form.shippingCourier, form.city"
                            x-cloak
                        >
                            <svg
                                class="me-3 mt-0.5 inline size-4 flex-shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"
                                />
                            </svg>
                            <span class="sr-only">Informasi</span>
                            <p class="text-sm tracking-tight">
                                <span class="font-medium">Perhatian!</span>
                                Silakan pilih provinsi, kabupaten/kota, dan salah satu kurir ekspedisi pengiriman diatas
                                terlebih dahulu sebelum memilih layanan pengiriman ekspedisi.
                            </p>
                        </div>
                        <div
                            wire:loading.flex
                            wire:target="form.shippingCourier, form.city"
                            class="items-center rounded-lg border border-neutral-300 p-4 text-sm font-medium text-black shadow-sm"
                            x-cloak
                        >
                            <div role="status">
                                <svg
                                    class="me-2 size-6 animate-spin fill-primary-600 text-primary-200"
                                    viewBox="0 0 100 101"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                        fill="currentColor"
                                    />
                                    <path
                                        d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                        fill="currentFill"
                                    />
                                </svg>
                                <span class="sr-only">Sedang diproses...</span>
                            </div>
                            Sedang diproses...
                        </div>
                    @else
                        <ul class="grid grid-cols-1 gap-4">
                            @foreach ($this->selectedCourierServices as $service)
                                <li class="relative">
                                    <x-form.radio
                                        :inputAttributes="
                                            [
                                                'wire:model.lazy' => 'form.shippingCourierService',
                                                'id' => 'expedition-' . strtolower($service['courier_code']) . '-service-' . strtolower($service['service']),
                                                'name' => 'select-courier-expedition-service',
                                                'value' => strtolower($service['service']),
                                                'x-on:input' => 'selected = \'' . strtolower($service['service']) . '\'',
                                            ]
                                        "
                                        :labelAttributes="
                                            [
                                                'for' => 'expedition-' . strtolower($service['courier_code']) . '-service-' . strtolower($service['service']),
                                                'wire:loading.class' => 'opacity-50 !cursor-wait hover:bg-white',
                                                'wire:target' => 'form.shippingCourier, form.city, form.shippingCourierService, handleComboboxChange',
                                            ]
                                        "
                                        :hasError="$errors->has('form.shippingCourierService')"
                                    >
                                        <img
                                            src="{{ asset('images/logos/shipping/' . $service['courier_code'] . '.webp') }}"
                                            alt="Logo {{ strtoupper($service['courier_code']) }}"
                                            class="h-auto w-12"
                                            loading="lazy"
                                        />
                                        <p class="inline-flex flex-col items-start gap-y-1 text-sm">
                                            <span class="font-semibold tracking-tight text-black">
                                                {{ strtoupper($service['courier_code'] . '-' . $service['service']) }}
                                            </span>
                                            <span class="font-medium tracking-tight text-black/50">
                                                {{ $service['description'] }}
                                            </span>
                                            <span class="w-full tracking-tight text-black">
                                                Estimasi waktu pengiriman: ± {{ $service['etd'] }} hari kerja (setelah
                                                pesanan telah dibayar)
                                            </span>
                                            <span class="w-full tracking-tight text-black">
                                                Ongkir: Rp
                                                {{ formatPrice($service['cost_value']) }}
                                            </span>
                                        </p>
                                    </x-form.radio>
                                    @if ($form->shippingCourierService === strtolower($service['service']))
                                        <svg
                                            class="absolute end-4 top-3 size-5 shrink-0 fill-primary stroke-primary-50"
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <circle cx="12" cy="12" r="10" />
                                            <path d="m9 12 2 2 4-4" />
                                        </svg>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <x-form.input-error :messages="$errors->get('form.shippingCourierService')" class="mt-2" />
                </div>
            </div>
        </fieldset>
    </div>
    <aside class="relative h-full w-full rounded-md border border-neutral-300 py-4 shadow-md lg:w-1/3">
        <div>
            <h2 class="mb-4 px-4 text-xl text-black">Ringkasan Belanja</h2>
            <div class="flex flex-col gap-y-2 px-4">
                @foreach ($form->items as $item)
                    <article wire:key="{{ $item->id }}" class="flex items-start gap-x-4">
                        <div class="size-20 shrink-0 overflow-hidden rounded-md bg-neutral-100">
                            <img
                                src="{{ asset('storage/uploads/product-images/' . $item->thumbnail) }}"
                                alt="Gambar produk {{ strtolower($item->name) }}"
                                class="aspect-square h-full w-full object-cover"
                                loading="lazy"
                            />
                        </div>
                        <div class="flex flex-col gap-y-1">
                            <h3 class="mb-1 max-w-40 truncate !text-base !font-semibold text-black md:max-w-60">
                                {{ $item->name }}
                            </h3>

                            @if ($item->variant && $item->variation)
                                <p class="text-sm tracking-tight text-black">
                                    {{ ucwords($item->variation) . ': ' . ucwords($item->variant) }}
                                </p>
                            @endif

                            <p class="text-sm tracking-tight text-black/70">
                                Jumlah:
                                <span class="font-medium text-black">{{ $item->quantity }}</span>
                            </p>
                        </div>
                        <div class="ml-auto shrink-0">
                            <p class="text-end font-medium tracking-tight text-black">
                                Rp {{ formatPrice($item->price) }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
            <hr class="my-4 border-neutral-300" />
            <dl class="grid grid-cols-2 gap-y-2 px-4">
                <dt class="mb-1 text-start tracking-tight text-black/70">Subtotal</dt>
                <dd class="mb-1 text-end font-medium tracking-tight text-black">
                    Rp {{ formatPrice($form->totalPrice) }}
                </dd>
                <dt class="mb-1 text-start tracking-tight text-black/70">Potongan Diskon</dt>
                <dd
                    @class([
                        'mb-1 text-end font-medium tracking-tight',
                        'text-black' => ! $form->discountAmount,
                        'text-teal-500' => $form->discountAmount,
                    ])
                >
                    - Rp {{ $form->discountAmount ? formatPrice($form->discountAmount) : '0' }}
                </dd>
                <dt class="inline-flex gap-x-2 text-start tracking-tight text-black/70">Ongkos Kirim</dt>
                <dd class="text-end font-medium tracking-tight text-black">
                    + Rp {{ $form->shippingCourierServiceTax ? formatPrice($form->shippingCourierServiceTax) : '0' }}
                </dd>
            </dl>
            <hr class="my-4 border-neutral-300" />
            <dl class="grid grid-cols-2 px-4">
                <dt class="text-start tracking-tight text-black/70">Total</dt>
                <dd class="text-end font-medium tracking-tight text-black">
                    Rp
                    {{ formatPrice($form->totalPrice - $form->discountAmount + $form->shippingCourierServiceTax) }}
                </dd>
            </dl>
            <hr class="my-4 border-neutral-300" />
            <div class="mb-4 px-4">
                <div class="mb-1 flex items-center justify-between">
                    <x-form.input-label for="note" value="Catatan Pesanan" :required="false" />
                    <span class="text-sm font-medium tracking-tight text-black/70">(opsional)</span>
                </div>
                <x-form.textarea
                    wire:model.lazy="form.note"
                    id="note"
                    name="note"
                    rows="3"
                    placeholder="Isikan catatan pesanan anda di sini..."
                    minlength="3"
                    maxlength="100"
                    :hasError="$errors->has('form.note')"
                ></x-form.textarea>
                <x-form.input-error :messages="$errors->get('form.note')" class="mt-2" />
            </div>
            <div class="px-4">
                <div class="flex">
                    <x-form.checkbox
                        wire:model.lazy="form.acceptTermsAndCondition"
                        id="accept-terms-and-condition"
                        name="accept-terms-and-condition"
                        required
                        :hasError="$errors->has('form.acceptTermsAndCondition')"
                    />
                    <label for="accept-terms-and-condition" class="ms-2 text-sm tracking-tight text-black">
                        Saya telah membaca dan menyetujui
                        <a href="{{ route('terms-and-conditions') }}" class="font-medium underline" target="_blank">
                            Syarat dan Ketentuan
                        </a>
                        toko.
                    </label>
                </div>
                <x-form.input-error :messages="$errors->get('form.acceptTermsAndCondition')" class="mt-2" />
            </div>
            <hr class="my-4 border-neutral-300" />
            <div class="px-4">
                <x-common.button type="submit" variant="primary" class="w-full">Checkout</x-common.button>
            </div>
        </div>
    </aside>
</form>
