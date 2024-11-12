<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Livewire\Forms\CategoryForm;
use Livewire\Volt\Component;

new class extends Component {
    public CategoryForm $form;

    public function save(CategoryController $controller)
    {
        $validated = $this->form->validate();

        $controller->store($validated);

        session()->flash('success', 'Data kategori ' . $validated['name'] . ' berhasil ditambahkan.');

        $this->redirectRoute('admin.categories.index', navigate: true);
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow-sm">
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Dasar Kategori</h2>
        </legend>
        <div class="p-4">
            <x-form.input-label for="name" value="Nama Kategori" />
            <x-form.input
                wire:model.lazy="form.name"
                id="name"
                class="mt-1 block w-full"
                type="text"
                name="name"
                required
                autofocus
            />
            <x-form.input-error :messages="$errors->get('form.name')" class="mt-2" />
        </div>
        @can('set primary categories')
            <div class="p-4">
                <div class="flex items-center">
                    <input
                        wire:model.lazy="form.isPrimary"
                        type="checkbox"
                        id="is-primary"
                        class="relative h-7 w-[3.25rem] cursor-pointer rounded-full border-transparent bg-neutral-200 p-px text-transparent transition-colors duration-200 ease-in-out before:inline-block before:size-6 before:translate-x-0 before:transform before:rounded-full before:bg-white before:shadow before:ring-0 before:transition before:duration-200 before:ease-in-out checked:border-primary checked:bg-none checked:text-primary checked:before:translate-x-full checked:before:bg-white focus:ring-primary focus:checked:border-primary disabled:pointer-events-none disabled:opacity-50"
                        aria-describedby="is-primary-error"
                    />
                    <label for="is-primary" class="ms-3 text-sm text-neutral-900">
                        Kategori Utama
                        <span class="text-red-500">*</span>
                    </label>
                </div>
                @error('form.isPrimary')
                    <p class="mt-2 text-xs text-red-500" id="is-primary-error">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        @endcan
    </fieldset>
    <div class="flex flex-col justify-end gap-4 p-4 md:flex-row">
        <x-common.button
            :href="route('admin.categories.index')"
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
