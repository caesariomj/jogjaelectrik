<?php

use App\Livewire\Forms\SubcategoryForm;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        return Category::select('id as value', 'name as label')->get();
    }

    public function save()
    {
        $validated = $this->form->validate();

        try {
            $this->authorize('update', $this->form->subcategory);

            DB::transaction(function () use ($validated) {
                $this->form->subcategory->update([
                    'category_id' => $validated['categoryId'],
                    'name' => strtolower($validated['name']),
                ]);
            });

            session()->flash('success', 'Subkategori ' . $validated['name'] . ' berhasil diubah.');
            $this->redirectRoute('admin.subcategories.index', navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
        } catch (QueryException $e) {
            Log::error('Database error during subcategory alteration: ' . $e->getMessage());

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah subkategori baru, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Unexpected subcategory alteration error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
        }
    }

    public function handleComboboxChange($value, $comboboxInstanceName)
    {
        if ($comboboxInstanceName == 'kategori') {
            $this->form->categoryId = $value;
        }
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow">
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Dasar Subkategori</h2>
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
                placeholder="Isikan nama subkategori disini..."
                minlength="3"
                maxlength="100"
                autocomplete="off"
                required
                :hasError="$errors->has('form.name')"
            />
            <x-form.input-error :messages="$errors->get('form.name')" class="mt-2" />
        </div>
    </fieldset>
    <div class="flex flex-col justify-end gap-4 p-4 md:flex-row">
        <x-common.button
            :href="route('admin.subcategories.index')"
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
