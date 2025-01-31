<?php

use App\Livewire\Forms\CategoryForm;
use App\Models\Category;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Component;

new class extends Component {
    public CategoryForm $form;

    public function mount(Category $category)
    {
        $this->form->setCategory($category);
    }

    /**
     * Update the category.
     */
    public function save()
    {
        $validated = $this->form->validate();

        if ($validated['isPrimary'] && ! $this->form->category->is_primary) {
            if (Category::queryPrimary()->count() >= 2) {
                $this->addError(
                    'form.isPrimary',
                    'Anda sudah memiliki 2 kategori utama. Maksimal kategori utama adalah 2.',
                );
                return;
            }
        }

        try {
            $this->authorize('update', $this->form->category);

            DB::transaction(function () use ($validated) {
                $this->form->category->update([
                    'name' => strtolower($validated['name']),
                    'is_primary' => $validated['isPrimary'],
                ]);
            });

            session()->flash('success', 'Kategori ' . $validated['name'] . ' berhasil diubah.');
            $this->redirectRoute('admin.categories.index', navigate: true);
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.categories.index'), navigate: true);
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
                    'operation' => 'Updating category data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah kategori ini, silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.categories.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('An unexpected error occurred', [
                'error_type' => 'Exception',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => request()->fullUrl(),
                'user_id' => auth()->id(),
                'context' => [
                    'operation' => 'Updating category data',
                    'component_name' => $this->getName(),
                ],
            ]);

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.categories.index'), navigate: true);
        }
    }
}; ?>

<form wire:submit.prevent="save" class="rounded-xl border border-neutral-300 bg-white shadow">
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Dasar Kategori</h2>
        </legend>
        <div class="p-4">
            <x-form.input-label for="name" value="Nama Kategori" />
            <x-form.input
                wire:model.lazy="form.name"
                id="name"
                class="mt-1 block w-full capitalize"
                type="text"
                name="name"
                placeholder="Isikan nama kategori disini..."
                minlength="3"
                maxlength="100"
                autocomplete="off"
                required
                autofocus
                :hasError="$errors->has('form.name')"
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
                    />
                    <label for="is-primary" class="mx-3 text-sm font-medium tracking-tight text-black">
                        Kategori Utama
                        <span class="text-red-500">*</span>
                    </label>
                    <x-common.tooltip
                        id="primary-category-information"
                        class="z-[3] w-72"
                        text="Kategori utama akan ditampilkan pada halaman utama. Anda hanya dapat menetapkan maksimal 2 kategori sebagai kategori utama."
                    />
                </div>
                <x-form.input-error :messages="$errors->get('form.isPrimary')" class="mt-2" />
            </div>
        @endcan
    </fieldset>
    <div class="flex flex-col justify-end gap-4 p-4 md:flex-row">
        <x-common.button
            :href="route('admin.categories.index')"
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
