<?php

use App\Http\Controllers\Admin\DiscountController;
use App\Livewire\Forms\DiscountForm;
use App\Models\Discount;
use Livewire\Volt\Component;

new class extends Component {
    public DiscountForm $form;

    public function mount(Discount $discount)
    {
        $this->form->setDiscount($discount);
    }

    public function save(DiscountController $controller)
    {
        $validated = $this->form->validate();

        $controller->update($validated, $this->form->discount);

        session()->flash('success', 'Data diskon ' . $validated['name'] . ' berhasil diubah.');

        $this->redirectRoute('admin.discounts.index', navigate: true);
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow-sm">
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
                    required
                    autofocus
                />
                <x-form.input-error :messages="$errors->get('form.name')" class="mt-2" />
            </div>
            <div class="mt-4">
                <div class="flex items-center justify-between">
                    <x-form.input-label for="description" value="Deskripsi Singkat Diskon" :required="false" />
                    <span class="text-xs tracking-tight text-black/80">(opsional)</span>
                </div>
                <x-form.textarea
                    wire:model.lazy="form.description"
                    id="description"
                    class="mt-1 w-full"
                    rows="10"
                    placeholder="Isikan deskripsi singkat diskon di sini..."
                    minlength="5"
                    maxlength="1000"
                />
                <x-form.input-error :messages="$errors->get('form.description')" class="mt-2" />
            </div>
            <div class="mt-4">
                <span class="mb-1 block cursor-default text-sm font-medium text-black">
                    Status Diskon
                    <span class="text-red-500">*</span>
                </span>
                <label class="inline-flex cursor-pointer items-center">
                    <input
                        wire:model.lazy="form.isActive"
                        type="checkbox"
                        id="is-primary"
                        class="relative h-7 w-[3.25rem] cursor-pointer rounded-full border-transparent bg-neutral-200 p-px text-transparent transition-colors duration-200 ease-in-out before:inline-block before:size-6 before:translate-x-0 before:transform before:rounded-full before:bg-white before:shadow before:ring-0 before:transition before:duration-200 before:ease-in-out checked:border-primary checked:bg-none checked:text-primary checked:before:translate-x-full checked:before:bg-white focus:ring-primary focus:checked:border-primary disabled:pointer-events-none disabled:opacity-50"
                        required
                    />
                    <span class="ms-3 text-sm font-medium text-black">Aktif</span>
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
                    required
                />
                <x-form.input-error :messages="$errors->get('form.code')" class="mt-2" />
            </div>
            <div class="mt-4 flex flex-col gap-4 md:flex-row">
                <div>
                    <span class="mb-1 block cursor-default text-sm font-medium text-black">
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
                                class="inline-flex w-full cursor-pointer items-center justify-start rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm tracking-tight text-black hover:border-primary hover:bg-primary-50 hover:text-primary peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
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
                                class="inline-flex w-full cursor-pointer items-center justify-start rounded-lg border border-neutral-300 bg-white px-4 py-2.5 text-sm tracking-tight text-black hover:border-primary hover:bg-primary-50 hover:text-primary peer-checked:border-primary peer-checked:bg-primary-50 peer-checked:text-primary"
                            >
                                Persentase
                            </label>
                        </div>
                    </div>
                    <x-form.input-error :messages="$errors->get('form.type')" class="mt-2" />
                </div>
                <div class="flex-grow">
                    <x-form.input-label for="value" value="Nilai Potongan Diskon" />
                    <div class="relative">
                        @if ($form->type == 'fixed')
                            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                                <span class="text-sm text-black/80">Rp</span>
                            </div>
                        @endif

                        <x-form.input
                            wire:model.lazy="form.value"
                            @class([
                                'mt-1 block w-full [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none',
                                'ps-11' => $form->type == 'fixed',
                                'pe-11' => $form->type == 'percentage',
                            ])
                            id="value"
                            type="number"
                            name="value"
                            placeholder="Isikan nilai potongan diskon disini..."
                            inputmode="numeric"
                            min="0"
                            max="99999999.99"
                            required
                        />

                        @if ($form->type == 'percentage')
                            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4">
                                <span class="text-sm text-black/80">%</span>
                            </div>
                        @endif
                    </div>
                    <x-form.input-error :messages="$errors->get('form.value')" class="mt-2" />
                </div>
            </div>
            <div class="mt-4 flex flex-col gap-4 md:flex-row">
                <div class="w-full md:w-1/2">
                    <div class="flex items-center justify-between">
                        <x-form.input-label for="usage-limit" value="Maksimal Pemakaian Diskon" :required="false" />
                        <span class="text-xs tracking-tight text-black/80">(opsional)</span>
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
                    />
                    <x-form.input-error :messages="$errors->get('form.usageLimit')" class="mt-2" />
                </div>
                <div class="w-full md:w-1/2">
                    <x-form.input-label for="minimum-purchase" value="Minimum Pembelian" />
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                            <span class="text-sm text-black/80">Rp</span>
                        </div>
                        <x-form.input
                            wire:model.lazy="form.minimumPurchase"
                            id="minimum-purchase"
                            class="mt-1 block w-full ps-11 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            type="number"
                            name="minimum-purchase"
                            placeholder="Isikan minimum pembelian disini..."
                            inputmode="numeric"
                            min="1"
                            max="99999999.99"
                            required
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
                        <span class="text-xs tracking-tight text-black/80">(opsional)</span>
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
                        <span class="text-xs tracking-tight text-black/80">(opsional)</span>
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
            wire:loading.class="opacity-50 pointer-events-none"
            wire:target="save"
            variant="secondary"
            wire:navigate
        >
            Batal
        </x-common.button>
        <x-common.button wire:loading.attr="disabled" wire:target="save" type="submit" variant="primary">
            <span wire:loading.remove wire:target="save">Simpan</span>
            <span
                wire:loading
                wire:target="save"
                class="inline-block size-5 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle text-white"
                role="status"
                aria-label="loading"
            >
                <span class="sr-only">Sedang diproses...</span>
            </span>
        </x-common.button>
    </div>
</form>
