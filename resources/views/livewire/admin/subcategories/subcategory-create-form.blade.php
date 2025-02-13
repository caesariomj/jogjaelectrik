<?php

use App\Livewire\Forms\SubcategoryForm;
use App\Models\Subcategory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public SubcategoryForm $form;

    /**
     * Get all categories with id as value, and name as label.
     */
    #[Computed]
    public function categories()
    {
        return DB::table('categories')
            ->select('id as value', 'name as label')
            ->get();
    }

    /**
     * Set categoryId value when the combobox component change.
     */
    public function handleComboboxChange($value, $comboboxInstanceName)
    {
        if ($comboboxInstanceName == 'kategori') {
            $this->form->categoryId = $value;
        }
    }

    /**
     * Create new subcategory.
     *
     * @return  void
     *
     * @throws  AuthorizationException if the user is not authorized to create new subcategory.
     * @throws  QueryException if a database query error occurred.
     * @throws  \Exception if an unexpected error occurred.
     */
    public function save()
    {
        $validated = $this->form->validate();

        try {
            $this->authorize('create', new Subcategory());

            DB::transaction(function () use ($validated) {
                Subcategory::create([
                    'category_id' => $validated['categoryId'],
                    'name' => strtolower($validated['name']),
                ]);
            });

            session()->flash('success', 'Subkategori ' . $validated['name'] . ' berhasil ditambahkan.');
            $this->redirectRoute('admin.subcategories.index', navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
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
                    'operation' => 'Creating subcategory data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menambahkan subkategori baru, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Creating subcategory data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.subcategories.index'), navigate: true);
        }
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow">
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Dasar Subkategori</h2>
        </legend>
        <div class="p-4">
            <div>
                <x-form.input-label for="select-category" value="Pilih Kategori" class="mb-1" />
                <x-form.combobox :options="$this->categories" name="kategori" id="select-category" />
                <x-form.input-error :messages="$errors->get('form.categoryId')" class="mt-2" />
            </div>
            <div class="mt-4">
                <x-form.input-label for="name" value="Nama Subkategori" />
                <x-form.input
                    wire:model.lazy="form.name"
                    id="name"
                    class="mt-1 block w-full"
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
