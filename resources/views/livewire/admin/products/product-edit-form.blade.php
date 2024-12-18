<?php

use App\Livewire\Forms\ProductForm;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\VariantCombination;
use App\Models\Variation;
use App\Models\VariationVariant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ProductForm $form;

    public bool $hasVariation = false;

    public function mount(Product $product)
    {
        $this->form->setProduct($product);
        $this->hasVariation = $this->form->variation['name'] === '' ? false : true;
    }

    #[Computed]
    public function subcategories()
    {
        return Subcategory::select('id as value', 'name as label')->get();
    }

    public function handleComboboxChange($value, $comboboxInstanceName)
    {
        if ($comboboxInstanceName == 'subkategori') {
            $this->form->subcategoryId = $value;
        }
    }

    public function deleteImage(string $id)
    {
        $image = $this->form->product->images()->find($id);

        if (! $image) {
            return;
        }

        try {
            $this->authorize('update', $this->form->product);

            DB::transaction(function () use ($image) {
                $filePath = 'product-images/' . $image->file_name;

                if (Storage::disk('public_uploads')->exists($filePath)) {
                    Storage::disk('public_uploads')->delete($filePath);
                }

                $image->delete();
            });

            session()->flash('success', 'Gambar produk berhasil dihapus.');
            return $this->redirectIntended(
                route('admin.products.edit', ['slug' => $this->form->product->slug]),
                navigate: true,
            );
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(
                route('admin.products.edit', ['slug' => $this->form->product->slug]),
                navigate: true,
            );
        } catch (QueryException $e) {
            Log::error('Database error during product image deletion: ' . $e->getMessage());

            session()->flash(
                'error',
                'Terjadi kesalahan dalam menghapus gambar produk ' .
                    $image->product->name .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(
                route('admin.products.edit', ['slug' => $this->form->product->slug]),
                navigate: true,
            );
        } catch (\Exception $e) {
            Log::error('Unexpected product image deletion error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(
                route('admin.products.edit', ['slug' => $this->form->product->slug]),
                navigate: true,
            );
        }
    }

    #[On('remove-variation')]
    public function removeVariation()
    {
        $this->reset('form.variation');
    }

    public function addVariationVariant()
    {
        $this->form->variation['variants'][] = [
            'name' => '',
            'price' => '',
            'priceDiscount' => '',
            'stock' => '',
            'variantSku' => '',
            'isVariantActive' => true,
        ];
    }

    public function removeVariationVariant($index)
    {
        unset($this->form->variation['variants'][$index]);
        $this->form->variation['variants'] = array_values($this->form->variation['variants']);
    }

    public function save()
    {
        $validated = $this->form->validate();

        $product = $this->form->product;

        try {
            $this->authorize('update', $product);

            DB::transaction(function () use ($validated, $product) {
                $price = $validated['price'];
                $priceDiscount = $validated['priceDiscount'] ?? null;

                if ($validated['variation']['name'] !== '') {
                    $variants = collect($validated['variation']['variants']);

                    $minPriceDiscountVariant = $variants
                        ->filter(function ($variant) {
                            return isset($variant['priceDiscount'], $variant['price']) &&
                                is_numeric($variant['priceDiscount']) &&
                                $variant['priceDiscount'] > 0;
                        })
                        ->sortBy('priceDiscount')
                        ->first();

                    $minPriceVariant =
                        $minPriceDiscountVariant ?:
                        $variants
                            ->filter(function ($variant) {
                                return isset($variant['price']) && is_numeric($variant['price']);
                            })
                            ->sortBy('price')
                            ->first();

                    $price = $minPriceVariant['price'];
                    $priceDiscount = $minPriceVariant['priceDiscount'] ?? null;
                }

                $product->update([
                    'subcategory_id' => $validated['subcategoryId'],
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'main_sku' => $validated['mainSku'],
                    'base_price' => str_replace('.', '', $price),
                    'base_price_discount' => $priceDiscount ? str_replace('.', '', $priceDiscount) : null,
                    'is_active' => (bool) $validated['isActive'],
                    'warranty' => $validated['warranty'],
                    'material' => $validated['material'],
                    'dimension' => $validated['length'] . 'x' . $validated['width'] . 'x' . $validated['height'],
                    'package' => $validated['package'],
                    'weight' => (int) str_replace('.', '', $validated['weight']),
                    'power' => is_numeric($validated['power']) ? (int) str_replace('.', '', $validated['power']) : null,
                    'voltage' => $validated['voltage'] !== '' ? $validated['voltage'] : null,
                ]);

                if ($validated['newThumbnail']) {
                    $oldThumbnail = $product
                        ->images()
                        ->thumbnail()
                        ->first();

                    if ($oldThumbnail) {
                        $filePath = 'product-images/' . $oldThumbnail->file_name;

                        if (Storage::disk('public_uploads')->exists($filePath)) {
                            Storage::disk('public_uploads')->delete($filePath);
                        }

                        $oldThumbnail->delete();
                    }

                    $thumbnailName = uniqid() . '_' . microtime(true) . '.' . $validated['newThumbnail']->extension();

                    $validated['newThumbnail']->storeAs('product-images', $thumbnailName, 'public_uploads');

                    $product->images()->create([
                        'file_name' => $thumbnailName,
                        'is_thumbnail' => true,
                    ]);
                }

                if ($validated['newImages']) {
                    foreach ($validated['newImages'] as $image) {
                        $fileName = uniqid() . '_' . microtime(true) . '.' . $image->extension();

                        $image->storeAs('product-images', $fileName, 'public_uploads');

                        $product->images()->create([
                            'file_name' => $fileName,
                            'is_thumbnail' => (bool) false,
                        ]);
                    }
                }

                $product->variants()->delete();

                if ($validated['variation']['name'] === '') {
                    $product->variants()->create([
                        'price' => str_replace('.', '', $price),
                        'price_discount' => $priceDiscount ? str_replace('.', '', $priceDiscount) : null,
                        'stock' => (int) str_replace('.', '', $validated['stock']),
                        'is_active' => (bool) $validated['isActive'],
                    ]);
                } else {
                    $variation = Variation::firstOrCreate([
                        'name' => strtolower($validated['variation']['name']),
                    ]);

                    foreach ($validated['variation']['variants'] as $variant) {
                        $variationVariant = $variation->variants()->firstOrCreate([
                            'name' => strtolower($variant['name']),
                        ]);

                        $productVariant = $product->variants()->create([
                            'variant_sku' => strtolower($variant['variantSku']),
                            'price' => str_replace('.', '', $variant['price']),
                            'price_discount' => $variant['priceDiscount']
                                ? str_replace('.', '', $variant['priceDiscount'])
                                : null,
                            'stock' => (int) str_replace('.', '', $variant['stock']),
                            'is_active' => (bool) $variant['isVariantActive'],
                        ]);

                        VariantCombination::create([
                            'product_variant_id' => $productVariant->id,
                            'variation_variant_id' => $variationVariant->id,
                        ]);
                    }
                }

                VariationVariant::whereDoesntHave('variantCombinations')->delete();
                Variation::whereDoesntHave('variants')->delete();
            });

            session()->flash('success', 'Data produk ' . $validated['name'] . ' berhasil diubah.');
            $this->redirectRoute('admin.products.index', navigate: true);
        } catch (AuthorizationException $e) {
            throw $e;
            session()->flash('error', $e->getMessage());
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (QueryException $e) {
            throw $e;
            Log::error('Database error during product alteration: ' . $e->getMessage());

            session()->flash(
                'error',
                'Terjadi kesalahan dalam mengubah produk ' .
                    $this->form->product->name .
                    ', silakan coba beberapa saat lagi.',
            );
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        } catch (\Exception $e) {
            throw $e;
            Log::error('Unexpected product alteration error: ' . $e->getMessage());

            session()->flash('error', 'Terjadi kesalahan tidak terduga, silakan coba beberapa saat lagi.');
            return $this->redirectIntended(route('admin.products.index'), navigate: true);
        }
    }
}; ?>

<form
    x-data="{ hasVariation: $wire.entangle('hasVariation') }"
    wire:submit.prevent="save"
    class="rounded-xl border border-neutral-300 bg-white shadow"
>
    <fieldset>
        <legend class="flex w-full border-b border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Dasar Produk</h2>
        </legend>
        <div x-data="thumbnailImageUpload" class="p-4">
            <span class="mb-1 block cursor-default text-sm font-medium tracking-tight text-black">
                Gambar Utama Produk
                <span class="text-red-500">*</span>
            </span>
            <div class="mb-4 flex items-center gap-5">
                @if ($form->newThumbnail)
                    <div class="relative size-28 overflow-hidden rounded-md border-2 border-dashed border-neutral-300">
                        <img
                            src="{{ $form->newThumbnail->temporaryUrl() }}"
                            alt="Gambar thumbnail produk"
                            class="h-full w-full rounded-md object-cover"
                        />
                        <div
                            x-show="isUploading"
                            class="absolute inset-0 flex flex-col items-center justify-center rounded-md bg-neutral-100"
                            x-cloak
                        >
                            <div
                                class="inline-block size-6 animate-spin rounded-full border-[3px] border-current border-t-transparent text-primary"
                                role="status"
                                aria-label="loading"
                            >
                                <span class="sr-only">Sedang diproses...</span>
                            </div>
                        </div>
                    </div>
                @elseif ($form->thumbnail)
                    <div class="relative size-28 overflow-hidden rounded-md border-2 border-dashed border-neutral-300">
                        <img
                            src="{{ asset('storage/uploads/product-images/' . $form->thumbnail->file_name) }}"
                            alt="Gambar thumbnail produk"
                            class="h-full w-full rounded-md object-cover"
                        />
                        <div
                            x-show="isUploading"
                            class="absolute inset-0 flex flex-col items-center justify-center rounded-md bg-neutral-100"
                            x-cloak
                        >
                            <div
                                class="inline-block size-6 animate-spin rounded-full border-[3px] border-current border-t-transparent text-primary"
                                role="status"
                                aria-label="loading"
                            >
                                <span class="sr-only">Sedang diproses...</span>
                            </div>
                        </div>
                    </div>
                @else
                    <span
                        x-bind:class="{
                            'bg-red-50 border-red-500':
                                {{ $errors->has('form.thumbnail') ? 'true' : 'false' }},
                            'bg-primary-50 border-primary':
                                ! {{ $errors->has('form.thumbnail') ? 'true' : 'false' }} && isDropping,
                            'bg-white border-neutral-300':
                                ! {{ $errors->has('form.thumbnail') ? 'true' : 'false' }} &&
                                ! isDropping,
                        }"
                        x-on:drop="isDropping = false"
                        x-on:drop.prevent="handleFileDrop($event)"
                        x-on:dragover.prevent="isDropping = true"
                        x-on:dragleave.prevent="isDropping = false"
                        class="relative flex size-28 shrink-0 items-center justify-center rounded-md border-2 border-dashed border-neutral-300 bg-neutral-50 text-black/70"
                    >
                        <svg
                            x-show="!isUploading"
                            class="size-8 text-black/70"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                            x-cloak
                        >
                            <path
                                d="M10.3 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10l-3.1-3.1a2 2 0 0 0-2.814.014L6 21"
                            />
                            <path d="m14 19.5 3-3 3 3" />
                            <path d="M17 22v-5.5" />
                            <circle cx="9" cy="9" r="2" />
                        </svg>
                        <div
                            x-show="isUploading"
                            class="absolute inset-0 flex flex-col items-center justify-center rounded-md bg-neutral-100"
                            x-cloak
                        >
                            <div
                                class="inline-block size-6 animate-spin rounded-full border-[3px] border-current border-t-transparent text-primary"
                                role="status"
                                aria-label="loading"
                            >
                                <span class="sr-only">Sedang diproses...</span>
                            </div>
                        </div>
                    </span>
                @endif
                <div class="flex-wrap space-y-4">
                    <div class="flex items-center space-x-4">
                        <label
                            for="thumbnail"
                            class="inline-flex cursor-pointer items-center justify-center gap-x-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold tracking-tight text-white transition-all hover:bg-primary-600"
                        >
                            <svg
                                class="size-4"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                <path d="M12 12v6" />
                                <path d="m15 15-3-3-3 3" />
                            </svg>
                            <span>Ubah gambar</span>
                            <input
                                @change="handleFileSelect"
                                id="thumbnail"
                                accept="image/png, image/jpg, image/jpeg"
                                type="file"
                                class="hidden"
                            />
                        </label>
                    </div>
                    <small class="text-center font-medium tracking-tight text-black/70">
                        Format file yang didukung: JPEG, JPG, PNG. Ukuran maksimal 1 MB
                    </small>
                </div>
            </div>
            <div
                x-show="isUploading"
                x-transition:enter="transition duration-300 ease-out"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition duration-300 ease-in"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-cloak
            >
                <div class="mb-2 flex items-center gap-x-3">
                    <span
                        class="flex size-8 items-center justify-center rounded-lg border border-neutral-300 text-black"
                    >
                        <svg
                            class="size-5 shrink-0"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                            <circle cx="9" cy="9" r="2" />
                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                        </svg>
                    </span>
                    <div>
                        <p x-text="file?.name" class="text-sm font-medium tracking-tight text-black"></p>
                        <p x-text="formatFileSize(file?.size)" class="text-xs tracking-tight text-black/70"></p>
                    </div>
                </div>
                <div class="flex items-center gap-x-3 whitespace-nowrap">
                    <div
                        class="flex h-2 w-full overflow-hidden rounded-full bg-neutral-200"
                        role="progressbar"
                        x-bind:aria-valuenow="progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="flex flex-col justify-center overflow-hidden whitespace-nowrap rounded-full bg-primary text-center text-xs text-white transition duration-500"
                            x-bind:style="{ width: progress + '%' }"
                        ></div>
                    </div>
                    <div class="w-8 text-end">
                        <span class="text-sm tracking-tight text-black" x-text="progress + '%'"></span>
                    </div>
                </div>
            </div>
            <x-form.input-error :messages="$errors->get('form.thumbnail')" class="mt-2" />
        </div>
        <div class="p-4">
            <x-form.input-label class="mb-1" for="name" value="Nama Produk" />
            <x-form.input
                wire:model.lazy="form.name"
                id="name"
                class="block w-full"
                type="text"
                name="name"
                placeholder="Isikan nama produk di sini..."
                minlength="5"
                maxlength="255"
                autocomplete="off"
                required
                autofocus
                :hasError="$errors->has('form.name')"
            />
            <x-form.input-error :messages="$errors->get('form.name')" class="mt-2" />
        </div>
        <div class="p-4">
            <span class="mb-1 block cursor-default text-sm font-medium tracking-tight text-black">
                Pilih Subkategori
                <span class="text-red-500">*</span>
            </span>
            <x-form.combobox
                :options="$this->subcategories"
                :selectedOption="$form->subcategoryId"
                name="subkategori"
                id="select-subcategory"
            />
            <x-form.input-error :messages="$errors->get('form.subcategoryId')" class="mt-2" />
        </div>
        <div class="p-4">
            <x-form.input-label class="mb-1" for="main-sku" value="SKU Utama Produk" />
            <x-form.input
                wire:model.lazy="form.mainSku"
                id="main-sku"
                class="block w-full"
                type="text"
                name="main-sku"
                placeholder="Isikan sku utama produk di sini..."
                minlength="5"
                maxlength="255"
                autocomplete="off"
                required
                :hasError="$errors->has('form.mainSku')"
            />
            <x-form.input-error :messages="$errors->get('form.mainSku')" class="mt-2" />
        </div>
        <div class="p-4">
            <x-form.input-label class="mb-1" for="description" value="Deskripsi Produk" />
            <x-form.textarea
                wire:model.lazy="form.description"
                id="description"
                rows="10"
                placeholder="Isikan deskripsi produk di sini..."
                minlength="10"
                maxlength="5000"
                required
                :hasError="$errors->has('form.description')"
            ></x-form.textarea>
            <x-form.input-error :messages="$errors->get('form.description')" class="mt-2" />
        </div>
        <div class="p-4">
            <span class="mb-1 block cursor-default text-sm font-medium text-black">
                Status Produk
                <span class="text-red-500">*</span>
            </span>
            <label class="inline-flex cursor-pointer items-center">
                <input
                    wire:model.lazy="form.isActive"
                    type="checkbox"
                    id="is-primary"
                    class="relative h-7 w-[3.25rem] cursor-pointer rounded-full border-transparent bg-neutral-200 p-px text-transparent transition-colors duration-200 ease-in-out before:inline-block before:size-6 before:translate-x-0 before:transform before:rounded-full before:bg-white before:shadow before:ring-0 before:transition before:duration-200 before:ease-in-out checked:border-primary checked:bg-none checked:text-primary checked:before:translate-x-full checked:before:bg-white focus:ring-primary focus:checked:border-primary disabled:pointer-events-none disabled:opacity-50"
                    aria-describedby="is-primary-error"
                />
                <span class="ms-3 text-sm font-medium text-black">Aktif</span>
            </label>
            <x-form.input-error :messages="$errors->get('form.isActive')" class="mt-2" />
        </div>
    </fieldset>
    <fieldset>
        <legend class="flex w-full border-y border-neutral-300 p-4">
            <h2 class="text-lg text-black">Gambar Produk</h2>
        </legend>
        <div class="p-4">
            <span class="mb-1 block cursor-default text-sm font-medium text-black">
                Gambar Produk
                <span class="text-red-500">*</span>
            </span>
            <div x-data="fileUpload">
                <div
                    class="h-96 w-full rounded-lg border-2 border-dashed p-8 text-black"
                    x-bind:class="{
                        'bg-red-50 border-red-500':
                            {{ $errors->has('form.newImages') || $errors->has('form.newImages.*') ? 'true' : 'false' }},
                        'bg-primary-50 border-primary':
                            ! {{ $errors->has('form.newImages') || $errors->has('form.newImages.*') ? 'true' : 'false' }} &&
                            isDropping,
                        'bg-white border-neutral-300':
                            ! {{ $errors->has('form.newImages') || $errors->has('form.newImages.*') ? 'true' : 'false' }} &&
                            ! isDropping,
                    }"
                    x-on:drop="isDropping = false"
                    x-on:drop.prevent="handleFileDrop($event)"
                    x-on:dragover.prevent="isDropping = true"
                    x-on:dragleave.prevent="isDropping = false"
                >
                    <div x-show="!isUploading" class="flex h-full flex-col items-center justify-center" x-cloak>
                        <svg
                            class="mb-4 size-12 text-black/70"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M10.3 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10l-3.1-3.1a2 2 0 0 0-2.814.014L6 21"
                            />
                            <path d="m14 19.5 3-3 3 3" />
                            <path d="M17 22v-5.5" />
                            <circle cx="9" cy="9" r="2" />
                        </svg>
                        <label
                            for="product-images"
                            class="mb-4 inline-flex cursor-pointer items-center justify-center gap-x-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white transition-all hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg
                                class="size-4"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                <path d="M12 12v6" />
                                <path d="m15 15-3-3-3 3" />
                            </svg>
                            <input
                                @change="handleFileSelect"
                                id="product-images"
                                accept="image/png, image/jpg, image/jpeg"
                                type="file"
                                class="sr-only"
                                aria-describedby="valid-file-formats"
                                multiple
                            />
                            Klik untuk mengupload
                        </label>
                        <p class="mb-2 text-pretty text-center font-medium tracking-tight text-black">
                            atau letakkan gambar produk anda di dalam kotak ini.
                        </p>
                        <small
                            id="valid-file-formats"
                            class="text-pretty text-center font-medium tracking-tight text-black/70"
                        >
                            Format file yang didukung: JPEG, JPG, atau PNG (Maks. 9 gambar dengan ukuran file 1 MB /
                            gambar)
                        </small>
                    </div>
                    <div
                        x-show="isUploading"
                        class="flex h-full flex-col items-center justify-center"
                        role="status"
                        aria-live="polite"
                        x-cloak
                    >
                        <div
                            class="mb-4 inline-block size-12 animate-spin rounded-full border-[4px] border-current border-t-transparent text-primary"
                            role="status"
                            aria-label="loading"
                        >
                            <span class="sr-only">Sedang diproses...</span>
                        </div>
                        <p class="text-center tracking-tight text-black">Sedang mengunggah file...</p>
                    </div>
                </div>
                <div
                    x-show="isUploading"
                    x-transition:enter="transition duration-300 ease-out"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition duration-300 ease-in"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="mt-4 flex flex-col"
                    x-cloak
                >
                    <div class="space-y-6">
                        <template x-for="(file, index) in files" :key="index">
                            <div>
                                <div class="mb-2 flex items-center gap-x-3">
                                    <span
                                        class="flex size-8 items-center justify-center rounded-lg border border-neutral-300 text-black"
                                    >
                                        <svg
                                            class="size-5 shrink-0"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                                            <circle cx="9" cy="9" r="2" />
                                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p
                                            x-text="file?.name"
                                            class="text-sm font-medium tracking-tight text-black"
                                        ></p>
                                        <p
                                            x-text="formatFileSize(file?.size)"
                                            class="text-xs tracking-tight text-black/70"
                                        ></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-x-3 whitespace-nowrap">
                                    <div
                                        class="flex h-2 w-full overflow-hidden rounded-full bg-neutral-200"
                                        role="progressbar"
                                        x-bind:aria-valuenow="progress"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                    >
                                        <div
                                            class="flex flex-col justify-center overflow-hidden whitespace-nowrap rounded-full bg-primary text-center text-xs text-white transition duration-500"
                                            x-bind:style="{ width: progress + '%' }"
                                        ></div>
                                    </div>
                                    <div class="w-8 text-end">
                                        <span
                                            class="text-sm tracking-tight text-black"
                                            x-text="progress + '%'"
                                        ></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                @if ($form->images->count() > 0 || (! is_null($form->newImages) && count($form->newImages) > 0))
                    <span class="mb-1 mt-4 block cursor-default text-sm font-medium tracking-tight text-black">
                        Preview Gambar Produk
                    </span>
                    <ul class="mt-2 grid grid-cols-2 gap-4 sm:grid-cols-6">
                        @foreach ($form->images as $image)
                            <li>
                                <figure
                                    class="relative flex h-full w-full flex-col items-center"
                                    :class="isDeleting ? 'opacity-50' : 'opacity-100'"
                                >
                                    <div
                                        class="relative aspect-square w-full overflow-hidden rounded-lg border border-neutral-300 shadow"
                                    >
                                        <img
                                            src="{{ asset('storage/uploads/product-images/' . $image->file_name) }}"
                                            alt="Preview Gambar Produk"
                                            class="absolute inset-0 h-full w-full object-cover"
                                        />
                                        <div
                                            x-show='isDeleting'
                                            class="absolute left-1/2 top-1/2 z-[1] -translate-x-1/2 -translate-y-1/2 opacity-100"
                                            role="status"
                                            x-cloak
                                        >
                                            <svg
                                                aria-hidden="true"
                                                class="h-10 w-10 animate-spin fill-primary text-neutral-200"
                                                viewBox="0 0 100 101"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
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
                                    </div>
                                    <figcaption
                                        class="mt-2 text-center text-sm font-medium tracking-tight text-black/70"
                                    >
                                        Gambar produk {{ $form->name . ' - ' . $loop->index + 1 }}
                                    </figcaption>
                                    <button
                                        type="button"
                                        x-on:click.prevent="$dispatch('open-modal', 'confirm-product-image-deletion-{{ $image->id }}')"
                                        wire:loading.class="opacity-50"
                                        class="absolute right-2 top-2 rounded-full bg-red-500 p-1 text-white transition-opacity duration-150"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </figure>
                            </li>
                            <x-common.modal
                                name="confirm-product-image-deletion-{{ $image->id }}"
                                :show="$errors->isNotEmpty()"
                                focusable
                            >
                                <div class="flex flex-col items-center p-6">
                                    <div class="mb-4 rounded-full bg-red-100 p-4" aria-hidden="true">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                            class="size-16 text-red-500"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <h2 class="mb-2 text-center !text-2xl text-black">
                                        Hapus Gambar Produk {{ ucwords($form->product->name) }}
                                    </h2>
                                    <p class="mb-8 text-center text-base font-medium tracking-tight text-black/70">
                                        Apakah anda yakin ingin menghapus gambar produk
                                        <strong>"{{ $form->product->name }}"</strong>
                                        ini ? Proses ini tidak dapat dibatalkan, seluruh data yang terkait dengan gambar
                                        produk ini akan dihapus dari sistem.
                                    </p>
                                    <div class="flex justify-end gap-4">
                                        <x-common.button
                                            variant="secondary"
                                            x-on:click="$dispatch('close')"
                                            wire:loading.class="!pointers-event-none !cursor-wait opacity-50"
                                            wire:target="deleteImage('{{ $image->id }}')"
                                        >
                                            Batal
                                        </x-common.button>
                                        <x-common.button
                                            wire:click="deleteImage('{{ $image->id }}')"
                                            variant="danger"
                                            wire:loading.attr="disabled"
                                            wire:target="deleteImage('{{ $image->id }}')"
                                        >
                                            <span wire:loading.remove wire:target="deleteImage('{{ $image->id }}')">
                                                Hapus Gambar Produk
                                            </span>
                                            <span
                                                wire:loading.flex
                                                wire:target="deleteImage('{{ $image->id }}')"
                                                class="items-center gap-x-2"
                                            >
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
                                </div>
                            </x-common.modal>
                        @endforeach

                        @if ($form->newImages)
                            @foreach ($form->newImages as $image)
                                <li>
                                    <figure
                                        class="relative flex h-full w-full flex-col items-center"
                                        :class="isDeleting ? 'opacity-50' : 'opacity-100'"
                                    >
                                        <div
                                            class="relative aspect-square w-full overflow-hidden rounded-lg border border-neutral-300 shadow"
                                        >
                                            <img
                                                src="{{ $image->temporaryUrl() }}"
                                                alt="Preview Gambar Produk"
                                                class="absolute inset-0 h-full w-full object-cover"
                                            />
                                            <div
                                                x-show='isDeleting'
                                                class="absolute left-1/2 top-1/2 z-[1] -translate-x-1/2 -translate-y-1/2 opacity-100"
                                                role="status"
                                                x-cloak
                                            >
                                                <svg
                                                    aria-hidden="true"
                                                    class="h-10 w-10 animate-spin fill-primary text-neutral-200"
                                                    viewBox="0 0 100 101"
                                                    fill="none"
                                                    xmlns="http://www.w3.org/2000/svg"
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
                                        </div>
                                        <figcaption
                                            class="mt-2 text-center text-sm font-medium tracking-tight text-black/70"
                                        >
                                            Gambar produk
                                            {{ $form->name . ' - ' . ($loop->index + $form->images->count()) + 1 }}
                                        </figcaption>
                                        <span
                                            class="absolute start-2 top-2 inline-flex items-center justify-center rounded-full bg-primary px-2 py-1 text-xs font-bold text-white"
                                        >
                                            Baru
                                        </span>
                                        <button
                                            type="button"
                                            @click="removeUpload('{{ $image->getFilename() }}')"
                                            class="absolute right-2 top-2 rounded-full bg-red-500 p-1 text-white transition-opacity duration-150"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                        </button>
                                    </figure>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                @endif
            </div>
            <x-form.input-error :messages="$errors->get('form.newImages')" class="mt-2" />
            <x-form.input-error :messages="$errors->get('form.newImages.*')" class="mt-2" />
        </div>
    </fieldset>
    <fieldset>
        <legend class="flex w-full border-y border-neutral-300 p-4">
            <h2 class="text-lg text-black">Spesifikasi Produk</h2>
        </legend>
        <div class="flex flex-col gap-4 p-4 lg:flex-row">
            <div class="w-full lg:w-1/2">
                <x-form.input-label class="mb-1" for="warranty" value="Informasi Garansi Produk" />
                <x-form.input
                    wire:model.lazy="form.warranty"
                    id="warranty"
                    class="block w-full"
                    type="text"
                    name="warranty"
                    placeholder="Isikan informasi garansi produk di sini..."
                    minlength="5"
                    maxlength="100"
                    autocomplete="off"
                    required
                    :hasError="$errors->has('form.warranty')"
                />
                <x-form.input-error :messages="$errors->get('form.warranty')" class="mt-2" />
            </div>
            <div class="w-full lg:w-1/2">
                <x-form.input-label class="mb-1" for="material" value="Bahan Material Produk" />
                <x-form.input
                    wire:model.lazy="form.material"
                    id="material"
                    class="block w-full"
                    type="text"
                    name="material"
                    placeholder="Isikan bahan material produk di sini..."
                    minlength="3"
                    maxlength="100"
                    autocomplete="off"
                    required
                    :hasError="$errors->has('form.material')"
                />
                <x-form.input-error :messages="$errors->get('form.material')" class="mt-2" />
            </div>
        </div>
        <div class="flex flex-col gap-4 p-4 lg:flex-row">
            <div class="w-full lg:w-1/2">
                <span class="mb-1 block cursor-default text-sm font-medium text-black">
                    Dimensi Paket
                    <span class="text-red-500">*</span>
                </span>
                <div class="grid grid-cols-1 gap-2 lg:grid-cols-3">
                    <div class="relative">
                        <x-form.input
                            wire:model.lazy="form.length"
                            id="length"
                            class="block w-full pe-11 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            type="number"
                            name="length"
                            placeholder="Panjang paket..."
                            min="1"
                            max="999"
                            inputmode="numeric"
                            autocomplete="off"
                            required
                            :hasError="$errors->has('form.length')"
                            x-mask="999"
                        />
                        <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4">
                            <span class="text-sm tracking-tight text-black/70">cm</span>
                        </div>
                    </div>
                    <div class="relative">
                        <x-form.input
                            wire:model.lazy="form.width"
                            id="width"
                            class="block w-full pe-11 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            type="number"
                            name="width"
                            placeholder="Lebar paket..."
                            min="1"
                            max="999"
                            inputmode="numeric"
                            autocomplete="off"
                            required
                            :hasError="$errors->has('form.width')"
                            x-mask="999"
                        />
                        <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4">
                            <span class="text-sm tracking-tight text-black/70">cm</span>
                        </div>
                    </div>
                    <div class="relative">
                        <x-form.input
                            wire:model.lazy="form.height"
                            id="height"
                            class="block w-full pe-11 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                            type="number"
                            name="height"
                            placeholder="Tinggi paket..."
                            min="1"
                            max="999"
                            inputmode="numeric"
                            autocomplete="off"
                            required
                            :hasError="$errors->has('form.height')"
                            x-mask="999"
                        />
                        <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4">
                            <span class="text-sm tracking-tight text-black/70">cm</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2 space-y-1">
                    <x-form.input-error :messages="$errors->get('form.length')" />
                    <x-form.input-error :messages="$errors->get('form.width')" />
                    <x-form.input-error :messages="$errors->get('form.height')" />
                </div>
            </div>
            <div class="w-full lg:w-1/2">
                <x-form.input-label class="mb-1" for="material" value="Berat Produk" />
                <div class="relative">
                    <x-form.input
                        wire:model.lazy="form.weight"
                        id="weight"
                        class="block w-full pe-14"
                        type="text"
                        name="weight"
                        placeholder="Isikan berat produk di sini..."
                        inputmode="numeric"
                        autocomplete="off"
                        required
                        :hasError="$errors->has('form.weight')"
                        x-mask:dynamic="$money($input, ',')"
                    />
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4">
                        <span class="text-sm tracking-tight text-black/70">gram</span>
                    </div>
                </div>
                <x-form.input-error :messages="$errors->get('form.weight')" class="mt-2" />
            </div>
        </div>
        <div class="flex flex-col gap-4 p-4 lg:flex-row">
            <div class="w-full lg:w-1/2">
                <x-form.input-label class="mb-1" for="package" value="Apa Yang Ada Di dalam Paket" />
                <x-form.input
                    wire:model.lazy="form.package"
                    id="package"
                    class="block w-full"
                    type="text"
                    name="package"
                    placeholder="Isikan apa yang ada di dalam paket produk di sini..."
                    minlength="5"
                    maxlength="100"
                    autocomplete="off"
                    required
                    :hasError="$errors->has('form.package')"
                />
                <x-form.input-error :messages="$errors->get('form.package')" class="mt-2" />
            </div>
            <div class="w-full lg:w-1/2">
                <div class="mb-1 flex items-center justify-between">
                    <x-form.input-label :required="false" for="power" value="Daya Listrik Produk" />
                    <span class="text-xs tracking-tight text-black/70">(opsional)</span>
                </div>
                <div class="relative">
                    <x-form.input
                        wire:model.lazy="form.power"
                        id="power"
                        class="block w-full pe-8"
                        type="text"
                        name="power"
                        placeholder="Isikan daya produk di sini..."
                        inputmode="numeric"
                        autocomplete="off"
                        :hasError="$errors->has('form.power')"
                        x-mask:dynamic="$money($input, ',')"
                    />
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4">
                        <span class="text-sm tracking-tight text-black/70">W</span>
                    </div>
                </div>
                <x-form.input-error :messages="$errors->get('form.power')" class="mt-2" />
            </div>
        </div>
        <div class="flex flex-col gap-4 p-4 lg:flex-row">
            <div class="w-full lg:w-1/2">
                <div class="mb-1 flex items-center justify-between">
                    <x-form.input-label :required="false" for="voltage" value="Tegangan Listrik Produk" />
                    <span class="text-xs tracking-tight text-black/70">(opsional)</span>
                </div>
                <div class="relative">
                    <x-form.input
                        wire:model.lazy="form.voltage"
                        id="voltage"
                        class="block w-full pe-8"
                        type="text"
                        name="voltage"
                        placeholder="Isikan tegangan produk di sini..."
                        inputmode="numeric"
                        autocomplete="off"
                        :hasError="$errors->has('form.voltage')"
                        x-mask:dynamic="$input.length > 3 ? '999-999' : '999'"
                    />
                    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4">
                        <span class="text-sm tracking-tight text-black/70">V</span>
                    </div>
                </div>
                <x-form.input-error :messages="$errors->get('form.voltage')" class="mt-2" />
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend class="flex w-full items-center justify-between border-y border-neutral-300 p-4">
            <h2 class="text-lg text-black">Informasi Lanjutan Produk</h2>
            <x-common.button
                x-show="!hasVariation"
                x-on:click="hasVariation = true"
                variant="secondary"
                class="relative !px-6"
                x-cloak
            >
                Aktifkan Variasi
                <span class="sr-only">Aktifkan Variasi Produk</span>
                <div class="absolute end-0 top-0">
                    <span class="relative flex h-3 w-3">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"
                        ></span>
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-red-500"></span>
                    </span>
                </div>
            </x-common.button>
        </legend>
        <div x-show="!hasVariation" class="flex flex-col gap-4 p-4 lg:flex-row" x-cloak>
            <div class="w-full lg:w-1/2">
                <x-form.input-label class="mb-1" for="price" value="Harga Produk" />
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                        <span class="text-sm tracking-tight text-black/70">Rp</span>
                    </div>
                    <x-form.input
                        wire:model.lazy="form.price"
                        id="price"
                        class="block w-full ps-11"
                        type="text"
                        name="price"
                        placeholder="Isikan harga produk di sini..."
                        inputmode="numeric"
                        autocomplete="off"
                        :hasError="$errors->has('form.price')"
                        x-bind:required="!hasVariation"
                        x-bind:disabled="hasVariation"
                        x-mask:dynamic="$money($input, ',')"
                    />
                </div>
                <x-form.input-error :messages="$errors->get('form.price')" class="mt-2" />
            </div>
            <div class="w-full lg:w-1/2">
                <div class="mb-1 flex items-center justify-between">
                    <x-form.input-label :required="false" for="price-discount" value="Harga Diskon Produk" />
                    <span class="text-xs tracking-tight text-black/70">(opsional)</span>
                </div>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4">
                        <span class="text-sm tracking-tight text-black/70">Rp</span>
                    </div>
                    <x-form.input
                        wire:model.lazy="form.priceDiscount"
                        id="price-discount"
                        class="block w-full ps-11"
                        type="text"
                        name="price-discount"
                        placeholder="Isikan harga diskon produk di sini..."
                        inputmode="numeric"
                        autocomplete="off"
                        :hasError="$errors->has('form.priceDiscount')"
                        x-bind:disabled="hasVariation"
                        x-mask:dynamic="$money($input, ',')"
                    />
                </div>
                <x-form.input-error :messages="$errors->get('form.priceDiscount')" class="mt-2" />
            </div>
        </div>
        <div x-show="!hasVariation" class="p-4" x-cloak>
            <x-form.input-label class="mb-1" for="stock" value="Stok Produk" />
            <x-form.input
                wire:model.lazy="form.stock"
                id="stock"
                class="block w-full [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                type="number"
                name="stock"
                min="1"
                max="999"
                inputmode="numeric"
                placeholder="Isikan stok produk di sini..."
                autocomplete="off"
                :hasError="$errors->has('form.stock')"
                x-bind:required="!hasVariation"
                x-bind:disabled="hasVariation"
                x-mask:dynamic="$money($input, ',')"
            />
            <x-form.input-error :messages="$errors->get('form.stock')" class="mt-2" />
        </div>
        <div x-show="hasVariation" class="p-4" x-cloak>
            <x-form.input-label class="mb-1" for="variation-name" value="Nama Variasi Produk" />
            <div class="flex gap-2">
                <div class="flex-grow">
                    <x-form.input
                        wire:model.lazy="form.variation.name"
                        id="variation-name"
                        class="block w-full"
                        type="text"
                        name="variation-name"
                        placeholder="Isikan nama variasi produk di sini... (contoh: warna)"
                        minlength="3"
                        maxlength="50"
                        autocomplete="off"
                        :hasError="$errors->has('form.variation.name')"
                        x-bind:required="hasVariation"
                    />
                </div>
                <button
                    type="button"
                    x-on:click="
                        hasVariation = false
                        $wire.dispatch('remove-variation')
                    "
                    class="rounded-full p-3 text-red-500 hover:bg-red-50"
                >
                    <svg
                        class="size-5"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M3 6h18" />
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                        <line x1="10" x2="10" y1="11" y2="17" />
                        <line x1="14" x2="14" y1="11" y2="17" />
                    </svg>
                    <span class="sr-only">Hapus variasi produk</span>
                </button>
            </div>
            <x-form.input-error :messages="$errors->get('form.variation.name')" class="mt-2" />
        </div>
        <div x-show="hasVariation" class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2" x-cloak>
            @foreach ($form->variation['variants'] as $index => $variant)
                <div>
                    <x-form.input-label class="mb-1" for="variant-{{ $index }}-name" value="Nama Varian Produk" />
                    <div class="flex gap-2">
                        <div class="flex-grow">
                            <x-form.input
                                wire:model.lazy="form.variation.variants.{{ $index }}.name"
                                id="variant-{{ $index }}-name"
                                class="block w-full"
                                type="text"
                                name="variant-{{ $index }}-name"
                                placeholder="Isikan nama varian produk di sini... (contoh: hitam)"
                                minlength="3"
                                maxlength="50"
                                autocomplete="off"
                                :hasError="$errors->has('form.variation.variants.' . $index . '.name')"
                                x-bind:required="hasVariation"
                            />
                        </div>
                        @if (count($form->variation['variants']) > 1)
                            <button
                                type="button"
                                wire:click="removeVariationVariant({{ $index }})"
                                class="rounded-full p-3 text-red-500 hover:bg-red-50 disabled:cursor-wait disabled:opacity-50"
                                wire:loading.attr="disabled"
                                wire:target="removeVariationVariant({{ $index }})"
                            >
                                <svg
                                    class="size-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    wire:loading.remove
                                    wire:target="removeVariationVariant({{ $index }})"
                                >
                                    <path d="M3 6h18" />
                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                    <line x1="10" x2="10" y1="11" y2="17" />
                                    <line x1="14" x2="14" y1="11" y2="17" />
                                </svg>
                                <div
                                    class="inline-block size-5 animate-spin rounded-full border-[3px] border-current border-t-transparent align-middle"
                                    role="status"
                                    aria-label="loading"
                                    wire:loading
                                    wire:target="removeVariationVariant({{ $index }})"
                                >
                                    <span class="sr-only">Sedang diproses...</span>
                                </div>
                                <span class="sr-only">Hapus varian produk</span>
                            </button>
                        @endif
                    </div>
                    <x-form.input-error
                        :messages="$errors->get('form.variation.variants.' . $index . '.name')"
                        class="mt-2"
                    />
                </div>
            @endforeach

            @if (count($form->variation['variants']) < 9)
                <div
                    class="flex flex-col items-center gap-2 justify-self-center md:flex-row md:self-end md:justify-self-start"
                >
                    <x-common.button
                        wire:click="addVariationVariant"
                        variant="outline"
                        class="h-fit w-fit !px-6"
                        wire:loading.attr="disabled"
                        wire:target="addVariationVariant"
                    >
                        <svg
                            wire:loading.remove
                            wire:target="addVariationVariant"
                            class="size-4 shrink-0"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            viewBox="0 0 256 256"
                        >
                            <path
                                d="M224,128a8,8,0,0,1-8,8H136v80a8,8,0,0,1-16,0V136H40a8,8,0,0,1,0-16h80V40a8,8,0,0,1,16,0v80h80A8,8,0,0,1,224,128Z"
                            />
                        </svg>
                        <span wire:loading.remove wire:target="addVariationVariant">Tambah Varian</span>
                        <svg
                            wire:loading
                            wire:target="addVariationVariant"
                            class="size-5 animate-spin text-black/70"
                            width="16"
                            height="16"
                            fill="currentColor"
                            viewBox="0 0 256 256"
                            aria-hidden="true"
                        >
                            <path
                                d="M232,128a104,104,0,0,1-208,0c0-41,23.81-78.36,60.66-95.27a8,8,0,0,1,6.68,14.54C60.15,61.59,40,93.27,40,128a88,88,0,0,0,176,0c0-34.73-20.15-66.41-51.34-80.73a8,8,0,0,1,6.68-14.54C208.19,49.64,232,87,232,128Z"
                            />
                        </svg>
                        <span wire:loading wire:target="addVariationVariant" class="text-black/70">
                            Sedang Diproses
                        </span>
                    </x-common.button>
                    <small class="tracking-tight text-black/70">(maks. 9 varian / produk)</small>
                </div>
            @endif
        </div>
    </fieldset>
    <div x-show="hasVariation" class="flex w-full border-y border-neutral-300 p-4" x-cloak>
        <h2 class="text-lg text-black">Tabel Variasi Produk</h2>
    </div>
    <div x-show="hasVariation" class="flex flex-col" x-cloak>
        <div class="overflow-x-auto md:-mx-1.5">
            <div class="inline-block min-w-full p-2 align-middle md:p-4">
                <div class="overflow-hidden rounded-lg border border-neutral-300">
                    <table class="w-full table-fixed divide-y divide-neutral-300 bg-white">
                        <thead>
                            <tr>
                                <th
                                    scope="col"
                                    class="w-36 border-r border-neutral-300 px-6 py-3 text-center text-sm font-medium tracking-tight text-black/70"
                                >
                                    Nama Variasi
                                </th>
                                <th
                                    scope="col"
                                    class="w-40 border-r border-neutral-300 px-6 py-3 text-center text-sm font-medium tracking-tight text-black/70"
                                >
                                    Varian
                                </th>
                                <th
                                    scope="col"
                                    class="w-56 border-r border-neutral-300 px-6 py-3 text-start text-sm font-medium tracking-tight text-black/70"
                                >
                                    Harga
                                    <span class="text-sm text-red-500">*</span>
                                </th>
                                <th
                                    scope="col"
                                    class="w-56 border-r border-neutral-300 px-6 py-3 text-start text-sm font-medium tracking-tight text-black/70"
                                >
                                    Harga Diskon
                                    <span class="text-sm text-black/70">(opsional)</span>
                                </th>
                                <th
                                    scope="col"
                                    class="w-28 border-r border-neutral-300 px-6 py-3 text-start text-sm font-medium tracking-tight text-black/70"
                                >
                                    Stok
                                    <span class="text-sm text-red-500">*</span>
                                </th>
                                <th
                                    scope="col"
                                    class="w-56 border-r px-6 py-3 text-start text-sm font-medium tracking-tight text-black/70"
                                >
                                    Kode Varian
                                    <span class="text-sm text-red-500">*</span>
                                </th>
                                <th
                                    scope="col"
                                    class="w-28 border-neutral-300 px-6 py-3 text-start text-sm font-medium tracking-tight text-black/70"
                                >
                                    Aktif
                                    <span class="text-sm text-red-500">*</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-300">
                            @foreach ($form->variation['variants'] as $index => $variant)
                                <tr>
                                    @if ($loop->first)
                                        <td
                                            class="w-36 whitespace-nowrap border-r border-neutral-300 px-6 py-3 text-center text-sm tracking-tight text-black"
                                            rowspan="{{ count($form->variation['variants']) }}"
                                        >
                                            {{ $form->variation['name'] ?? '-' }}
                                        </td>
                                    @endif

                                    <td
                                        class="w-40 whitespace-nowrap border-r border-neutral-300 px-6 py-3 text-center text-sm tracking-tight text-black"
                                    >
                                        {{ $variant['name'] ?? '-' }}
                                    </td>
                                    <td class="w-56 whitespace-nowrap border-r border-neutral-300 px-6 py-3">
                                        <x-form.input
                                            wire:model.lazy="form.variation.variants.{{ $index }}.price"
                                            id="variant-{{ $index }}-price"
                                            class="block w-full"
                                            type="text"
                                            name="variant-{{ $index }}-price"
                                            placeholder="Harga..."
                                            inputmode="numeric"
                                            autocomplete="off"
                                            :hasError="$errors->has('form.variation.variants.' . $index . '.price')"
                                            x-bind:required="hasVariation"
                                            x-mask:dynamic="$money($input, ',')"
                                        />
                                    </td>
                                    <td class="w-56 whitespace-nowrap border-r border-neutral-300 px-6 py-3">
                                        <x-form.input
                                            wire:model.lazy="form.variation.variants.{{ $index }}.priceDiscount"
                                            id="variant-{{ $index }}-price-discount"
                                            class="block w-full"
                                            type="text"
                                            name="variant-{{ $index }}-price-discount"
                                            placeholder="Harga diskon..."
                                            inputmode="numeric"
                                            autocomplete="off"
                                            :hasError="$errors->has('form.variation.variants.' . $index . '.priceDiscount')"
                                            x-mask:dynamic="$money($input, ',')"
                                        />
                                    </td>
                                    <td class="w-28 whitespace-nowrap border-r border-neutral-300 px-6 py-3">
                                        <x-form.input
                                            wire:model.lazy="form.variation.variants.{{ $index }}.stock"
                                            id="variant-{{ $index }}-stock"
                                            class="block w-full [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                            type="number"
                                            name="variant-{{ $index }}-stock"
                                            placeholder="Stok..."
                                            min="1"
                                            max="999"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            :hasError="$errors->has('form.variation.variants.' . $index . '.stock')"
                                            x-bind:required="hasVariation"
                                            x-mask:dynamic="$money($input, ',')"
                                        />
                                    </td>
                                    <td class="w-56 whitespace-nowrap border-r border-neutral-300 px-6 py-3">
                                        <x-form.input
                                            wire:model.lazy="form.variation.variants.{{ $index }}.variantSku"
                                            id="variant-{{ $index }}-sku"
                                            class="block w-full"
                                            type="text"
                                            name="variant-{{ $index }}-sku"
                                            placeholder="Kode varian..."
                                            minlength="1"
                                            maxlength="255"
                                            autocomplete="off"
                                            :hasError="$errors->has('form.variation.variants.' . $index . '.variantSku')"
                                            x-bind:required="hasVariation"
                                        />
                                    </td>
                                    <td class="w-28 whitespace-nowrap px-6 py-3">
                                        <input
                                            wire:model.lazy="form.variation.variants.{{ $index }}.isVariantActive"
                                            id="is-variant-{{ $index }}-active"
                                            type="checkbox"
                                            name="is-variant-{{ $index }}-active"
                                            class="relative h-7 w-[3.25rem] cursor-pointer rounded-full border-transparent bg-neutral-200 p-px text-transparent transition-colors duration-200 ease-in-out before:inline-block before:size-6 before:translate-x-0 before:transform before:rounded-full before:bg-white before:shadow before:ring-0 before:transition before:duration-200 before:ease-in-out checked:border-primary checked:bg-none checked:text-primary checked:before:translate-x-full checked:before:bg-white focus:ring-primary focus:checked:border-primary disabled:pointer-events-none disabled:opacity-50"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="space-y-1 p-2">
            @foreach ($form->variation['variants'] as $index => $variant)
                <x-form.input-error :messages="$errors->get('form.variation.variants.' . $index . '.price')" />
                <x-form.input-error :messages="$errors->get('form.variation.variants.' . $index . '.priceDiscount')" />
                <x-form.input-error :messages="$errors->get('form.variation.variants.' . $index . '.stock')" />
                <x-form.input-error :messages="$errors->get('form.variation.variants.' . $index . '.variantSku')" />
                <x-form.input-error
                    :messages="$errors->get('form.variation.variants.' . $index . '.isVariantActive')"
                />
            @endforeach
        </div>
    </div>
    <div class="flex flex-col justify-end gap-4 p-4 md:flex-row">
        <x-common.button
            :href="route('admin.products.index')"
            variant="secondary"
            wire:loading.class="!pointers-event-none !cursor-wait opacity-50"
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

@push('scripts')
    @script
        <script>
            Alpine.data('thumbnailImageUpload', () => {
                return {
                    file: null,
                    isDropping: false,
                    isUploading: false,
                    progress: 0,
                    handleFileSelect(event) {
                        if (event.target.files.length > 0) {
                            this.file = event.target.files[0];
                            this.uploadFile(event.target.files[0]);
                        }
                    },
                    handleFileDrop(event) {
                        if (event.dataTransfer.files.length > 0) {
                            this.file = event.dataTransfer.files[0];
                            this.uploadFile(event.dataTransfer.files[0]);
                        }
                    },
                    uploadFile(file) {
                        this.isUploading = true;
                        $wire.upload(
                            'form.newThumbnail',
                            file,
                            () => {
                                this.isUploading = false;
                                this.progress = 0;
                            },
                            () => {
                                this.isUploading = false;
                                this.progress = 0;
                            },
                            (event) => {
                                this.progress = event.detail.progress;
                            },
                        );
                    },
                    removeUpload(filename) {
                        this.isDeleting = true;
                        $wire.$removeUpload('form.newThumbnail', filename, () => {
                            this.isDeleting = false;
                        });
                    },
                    formatFileSize(bytes) {
                        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                        if (bytes === 0) return '0 Bytes';
                        const i = Math.floor(Math.log(bytes) / Math.log(1024));
                        return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sizes[i]}`;
                    },
                };
            });

            Alpine.data('fileUpload', () => {
                return {
                    files: [],
                    isDropping: false,
                    isUploading: false,
                    isDeleting: false,
                    progress: 0,
                    handleFileSelect(event) {
                        if (event.target.files.length) {
                            Array.from(event.target.files).forEach((file) => {
                                this.files.push(file);
                            });
                            this.uploadFiles(event.target.files);
                        }
                    },
                    handleFileDrop(event) {
                        if (event.dataTransfer.files.length > 0) {
                            Array.from(event.dataTransfer.files).forEach((file) => {
                                this.files.push(file);
                            });
                            this.uploadFiles(event.dataTransfer.files);
                        }
                    },
                    uploadFiles(files) {
                        const $this = this;
                        this.isUploading = true;
                        $wire.$uploadMultiple(
                            'form.newImages',
                            files,
                            function (success) {
                                $this.files = [];
                                $this.isUploading = false;
                                $this.progress = 0;
                            },
                            function (error) {
                                $this.files = [];
                                $this.isUploading = false;
                                $this.progress = 0;
                                console.log('error', error);
                            },
                            function (event) {
                                $this.progress = event.detail.progress;
                            },
                        );
                    },
                    removeUpload(filename) {
                        const $this = this;
                        this.isDeleting = true;
                        $wire.$removeUpload('form.newImages', filename, function (success) {
                            $this.isDeleting = false;
                        });
                    },
                    formatFileSize(bytes) {
                        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                        if (bytes === 0) return '0 Bytes';
                        const i = Math.floor(Math.log(bytes) / Math.log(1024));
                        return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sizes[i]}`;
                    },
                };
            });
        </script>
    @endscript
@endpush
