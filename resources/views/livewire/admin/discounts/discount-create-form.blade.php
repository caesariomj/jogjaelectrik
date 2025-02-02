<?php

use App\Livewire\Forms\DiscountForm;
use App\Models\Discount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Volt\Component;

new class extends Component {
    public DiscountForm $form;

    /**
     * Reset discount value and max amount to empty after discount type updated.
     */
    public function updatedFormType()
    {
        $this->form->value = '';
        $this->form->maxDiscountAmount = '';
    }

    /**
     * Create a new discount.
     */
    public function save()
    {
        $validated = $this->form->validate();

        try {
            $this->authorize('create', new Discount());

            DB::transaction(function () use ($validated) {
                Discount::create([
                    'name' => strtolower($validated['name']),
                    'description' => $validated['description'] !== '' ? $validated['description'] : null,
                    'code' => Str::slug($validated['code']),
                    'type' => $validated['type'],
                    'value' =>
                        $validated['type'] === 'fixed'
                            ? (float) str_replace('.', '', $validated['value'])
                            : (float) $validated['value'],
                    'max_discount_amount' =>
                        $validated['type'] === 'percentage'
                            ? (float) str_replace('.', '', $validated['maxDiscountAmount'])
                            : null,
                    'start_date' => $validated['startDate'] !== '' ? $validated['startDate'] : null,
                    'end_date' => $validated['endDate'] !== '' ? $validated['endDate'] : null,
                    'usage_limit' => is_numeric($validated['usageLimit'])
                        ? (int) str_replace('.', '', $validated['usageLimit'])
                        : null,
                    'minimum_purchase' => is_numeric($validated['minimumPurchase'])
                        ? (int) str_replace('.', '', $validated['minimumPurchase'])
                        : null,
                    'is_active' => (bool) $validated['isActive'],
                ]);
            });

            session()->flash('success', 'Diskon ' . $validated['name'] . ' berhasil ditambahkan.');
            $this->redirectRoute('admin.discounts.index', navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
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
                    'operation' => 'Creating discount data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menambahkan diskon baru, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Creating category data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.discounts.index'), navigate: true);
        }
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow">
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Dasar Diskon</h2>
        </legend>
        <div class="p-4">
            <div>
                <x-form.input-label for="name" value="Nama Diskon" />
                <x-form.input
                    wire:model.lazy="form.name"
                    id="name"
                    class="mt-1 block w-full"
                    type="text"
                    name="name"
                    placeholder="Isikan nama diskon disini..."
                    minlength="3"
                    maxlength="100"
                    autocomplete="off"
                    required
                    autofocus
                    :hasError="$errors->has('form.name')"
                />
                <x-form.input-error :messages="$errors->get('form.name')" class="mt-2" />
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-between">
                    <x-form.input-label for="description" value="Deskripsi Singkat Diskon" :required="false" />
                    <span class="text-xs tracking-tight text-black/70">(opsional)</span>
                </div>
                <x-form.textarea
                    wire:model.lazy="form.description"
                    id="description"
                    class="mt-1 w-full"
                    rows="5"
                    placeholder="Isikan deskripsi singkat diskon di sini..."
                    minlength="5"
                    maxlength="1000"
                    :hasError="$errors->has('form.description')"
                />
                <x-form.input-error :messages="$errors->get('form.description')" class="mt-2" />
            </div>
            <div class="mt-4">
                <span class="mb-1 block cursor-default text-sm font-medium tracking-tight text-black">
                    Status Diskon
                    <span class="text-red-500">*</span>
                </span>
                <label class="inline-flex cursor-pointer items-center">
                    <input
                        wire:model.lazy="form.isActive"
                        type="checkbox"
                        id="is-primary"
                        class="relative h-7 w-[3.25rem] cursor-pointer rounded-full border-transparent bg-neutral-200 p-px text-transparent transition-colors duration-200 ease-in-out before:inline-block before:size-6 before:translate-x-0 before:transform before:rounded-full before:bg-white before:shadow before:ring-0 before:transition before:duration-200 before:ease-in-out checked:border-primary checked:bg-none checked:text-primary checked:before:translate-x-full checked:before:bg-white focus:ring-primary focus:checked:border-primary disabled:pointer-events-none disabled:opacity-50"
                    />
                    <p
                        class="ms-3 inline-flex flex-col items-start gap-1.5 text-sm font-medium tracking-tight text-black md:flex-row md:items-center"
                    >
                        {{ $form->isActive ? 'Aktif' : 'Non-aktif' }}
                        <span class="text-xs text-black/70">
                            {{ $form->isActive ? '(Diskon dapat dilihat dan digunakan oleh pelanggan)' : '(Diskon tidak dapat dilihat dan digunakan oleh pelanggan)' }}
                        </span>
                    </p>
                </label>
                <x-form.input-error :messages="$errors->get('form.isActive')" class="mt-2" />
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend class="flex w-full border-y border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Lanjutan Diskon</h2>
        </legend>
        <div class="p-4">
            <div>
                <x-form.input-label for="code" value="Kode Diskon" />
                <x-form.input
                    wire:model.lazy="form.code"
                    id="code"
                    class="mt-1 block w-full"
                    type="text"
                    name="code"
                    placeholder="Isikan kode diskon disini..."
                    minlength="3"
                    maxlength="50"
                    autocomplete="off"
                    required
                    :hasError="$errors->has('form.code')"
                />
                <x-form.input-error :messages="$errors->get('form.code')" class="mt-2" />
            </div>
            <div class="mt-4 flex flex-col gap-4 md:flex-row">
                <div class="flex-shrink-0">
                    <span class="mb-1 block cursor-default text-sm font-medium tracking-tight text-black">
                        Jenis Diskon
                        <span class="text-red-500">*</span>
                    </span>
                    <div class="mt-1 grid w-full grid-cols-2 gap-2">
                        <div>
                            <input
                                wire:model.lazy="form.type"
                                type="radio"
                                id="fixed-type"
                                value="fixed"
                                class="peer hidden"
                                required
                            />
                            <label
                                for="fixed-type"
                                class="inline-flex w-full cursor-pointer items-center justify-start rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm tracking-tight text-black hover:border-primary hover:bg-primary-50 hover:text-primary peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
                                wire:loading.class="opacity-50 cursor-wait"
                                wire:target="form.type"
                            >
                                Nominal
                            </label>
                        </div>
                        <div>
                            <input
                                wire:model.lazy="form.type"
                                type="radio"
                                id="percentage-type"
                                value="percentage"
                                class="peer hidden"
                                required
                            />
                            <label
                                for="percentage-type"
                                class="inline-flex w-full cursor-pointer items-center justify-start rounded-lg border border-neutral-300 bg-white px-4 py-3 text-sm tracking-tight text-black hover:border-primary hover:bg-primary-50 hover:text-primary peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
                                wire:loading.class="opacity-50 cursor-wait"
                                wire:target="form.type"
                            >
                                Persentase
                            </label>
                        </div>
                    </div>
                    <x-form.input-error :messages="$errors->get('form.type')" class="mt-2" />
                </div>
                <div
                    @class([
                        'flex-grow',
                        '' => $form->type === 'fixed',
                        'flex flex-col items-start gap-4' => $form->type === 'percentage',
                    ])
                >
                    @if ($form->type === 'fixed')
                        <div>
                            <x-form.input-label for="value" value="Nilai Potongan Diskon" />
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                                    <span class="text-sm tracking-tight text-black/70">Rp</span>
                                </div>
                                <x-form.input
                                    wire:model.lazy="form.value"
                                    id="value"
                                    class="mt-1 block w-full ps-11"
                                    type="text"
                                    name="value"
                                    placeholder="Isikan nilai potongan diskon disini..."
                                    inputmode="numeric"
                                    autocomplete="off"
                                    required
                                    :hasError="$errors->has('form.value')"
                                    x-mask:dynamic="$money($input, ',')"
                                    wire:loading.attr="disabled"
                                    wire:target="form.type"
                                />
                            </div>
                            <x-form.input-error :messages="$errors->get('form.value')" class="mt-2" />
                        </div>
                    @else
                        <div class="w-full">
                            <x-form.input-label for="value" value="Nilai Potongan Diskon" />
                            <div class="relative">
                                <x-form.input
                                    wire:model.lazy="form.value"
                                    id="value"
                                    class="mt-1 block w-full pe-11"
                                    type="text"
                                    name="value"
                                    placeholder="Isikan nilai potongan diskon disini..."
                                    inputmode="numeric"
                                    autocomplete="off"
                                    required
                                    :hasError="$errors->has('form.value')"
                                    x-mask="99%"
                                    wire:loading.attr="disabled"
                                    wire:target="form.type"
                                />
                                <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4">
                                    <span class="text-sm tracking-tight text-black/70">%</span>
                                </div>
                            </div>
                            <x-form.input-error :messages="$errors->get('form.value')" class="mt-2" />
                        </div>
                        <div class="w-full">
                            <div class="inline-flex items-center gap-x-2">
                                <x-form.input-label for="max-discount-amount" value="Maksimal Potongan Harga" />
                                <x-common.tooltip
                                    id="maximum-discount-off-information"
                                    class="z-[3] w-80"
                                    text="Maksimal potongan harga digunakan untuk membatasi jumlah potongan harga tertinggi yang dapat diterima oleh pelanggan, terutama pada diskon berjenis persentase. Hal ini bertujuan untuk menghindari potongan harga yang terlalu besar pada produk dengan harga tinggi."
                                />
                            </div>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                                    <span class="text-sm tracking-tight text-black/70">Rp</span>
                                </div>
                                <x-form.input
                                    wire:model.lazy="form.maxDiscountAmount"
                                    id="max-discount-amount"
                                    class="mt-1 block w-full ps-11"
                                    type="text"
                                    name="max-discount-amount"
                                    placeholder="Isikan maksimal potongan harga disini..."
                                    inputmode="numeric"
                                    autocomplete="off"
                                    required
                                    :hasError="$errors->has('form.maxDiscountAmount')"
                                    x-mask:dynamic="$money($input, ',')"
                                    wire:loading.attr="disabled"
                                    wire:target="form.type"
                                />
                            </div>
                            <x-form.input-error :messages="$errors->get('form.maxDiscountAmount')" class="mt-2" />
                        </div>
                    @endif
                </div>
            </div>
            <div class="mt-4 flex flex-col gap-4 md:flex-row">
                <div class="w-full md:w-1/2">
                    <div class="flex items-center justify-between">
                        <x-form.input-label for="usage-limit" value="Maksimal Pemakaian Diskon" :required="false" />
                        <span class="text-xs tracking-tight text-black/70">(opsional)</span>
                    </div>
                    <x-form.input
                        wire:model.lazy="form.usageLimit"
                        id="usage-limit"
                        class="mt-1 block w-full [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                        type="number"
                        name="usage-limit"
                        placeholder="Isikan maksimal pemakaian diskon disini..."
                        inputmode="numeric"
                        min="1"
                        max="255"
                        autocomplete="off"
                        :hasError="$errors->has('form.usageLimit')"
                        x-mask="999"
                    />
                    <x-form.input-error :messages="$errors->get('form.usageLimit')" class="mt-2" />
                </div>
                <div class="w-full md:w-1/2">
                    <x-form.input-label for="minimum-purchase" value="Minimum Harga Pembelian" />
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                            <span class="text-sm tracking-tight text-black/70">Rp</span>
                        </div>
                        <x-form.input
                            wire:model.lazy="form.minimumPurchase"
                            id="minimum-purchase"
                            class="mt-1 block w-full ps-11"
                            type="text"
                            name="minimum-purchase"
                            placeholder="Isikan minimum harga pembelian disini..."
                            inputmode="numeric"
                            autocomplete="off"
                            required
                            :hasError="$errors->has('form.minimumPurchase')"
                            x-mask:dynamic="$money($input, ',')"
                        />
                    </div>
                    <x-form.input-error :messages="$errors->get('form.minimumPurchase')" class="mt-2" />
                </div>
            </div>
            <div class="mt-4 flex flex-col gap-4 lg:flex-row">
                <div class="w-full lg:w-1/2">
                    <div class="flex items-center justify-between">
                        <x-form.input-label
                            for="start-date"
                            class="mb-1"
                            value="Tanggal Mulai Diskon"
                            :required="false"
                        />
                        <span class="text-xs tracking-tight text-black/70">(opsional)</span>
                    </div>
                    <x-form.datepicker
                        wire:model.lazy="form.startDate"
                        class="w-full"
                        id="start-date"
                        name="start-date"
                        placeholder="Pilih tanggal mulai diskon..."
                    />
                    <x-form.input-error :messages="$errors->get('form.startDate')" class="mt-2" />
                </div>
                <div class="w-full lg:w-1/2">
                    <div class="flex items-center justify-between">
                        <x-form.input-label
                            for="end-date"
                            class="mb-1"
                            value="Tanggal Berakhir Diskon"
                            :required="false"
                        />
                        <span class="text-xs tracking-tight text-black/70">(opsional)</span>
                    </div>
                    <x-form.datepicker
                        wire:model.lazy="form.endDate"
                        :minDate="$form->startDate !== '' ? $form->startDate : 'today'"
                        class="w-full"
                        id="end-date"
                        name="end-date"
                        placeholder="Pilih tanggal berakhir diskon..."
                    />
                    <x-form.input-error :messages="$errors->get('form.endDate')" class="mt-2" />
                </div>
            </div>
        </div>
    </fieldset>
    <div class="flex flex-col justify-end gap-4 p-4 md:flex-row">
        <x-common.button
            :href="route('admin.discounts.index')"
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
