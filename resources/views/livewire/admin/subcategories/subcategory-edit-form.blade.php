<?php

use App\Http\Controllers\Admin\SubcategoryController;
use App\Livewire\Forms\SubcategoryForm;
use App\Models\Subcategory;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public SubcategoryForm $form;

    public function mount(Subcategory $subcategory)
    {
        $this->form->setSubcategory($subcategory);
    }

    #[Computed]
    public function categories()
    {
        return \App\Models\Category::select('id as value', 'name as label')->get();
    }

    public function save(SubcategoryController $controller)
    {
        $validated = $this->form->validate();

        $controller->update($validated, $this->form->subcategory);

        session()->flash('success', 'Data subkategori ' . $validated['name'] . ' berhasil diubah.');

        $this->redirectRoute('admin.subcategories.index', navigate: true);
    }

    public function handleComboboxChange($value, $comboboxInstanceName)
    {
        if ($comboboxInstanceName == 'kategori') {
            $this->form->categoryId = $value;
        }
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow-sm">
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Dasar Kategori</h2>
        </legend>
        <div class="p-4">
            <x-form.input-label for="select-category" value="Pilih Kategori" class="mb-1" />
            <x-form.combobox
                :options="$this->categories"
                :selectedOption="$form->categoryId"
                name="kategori"
                id="select-category"
            />
            <x-form.input-error :messages="$errors->get('form.categoryId')" class="mt-2" />
        </div>
        <div class="p-4">
            <x-form.input-label for="name" value="Nama Subkategori" />
            <x-form.input
                wire:model.lazy="form.name"
                id="name"
                class="mt-1 block w-full capitalize"
                type="text"
                name="name"
                required
            />
            <x-form.input-error :messages="$errors->get('form.name')" class="mt-2" />
        </div>
    </fieldset>
    <div class="flex flex-col justify-end gap-4 p-4 md:flex-row">
        <x-common.button
            :href="route('admin.subcategories.index')"
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
