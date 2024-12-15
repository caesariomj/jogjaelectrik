<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\VariantCombination;
use App\Models\Variation;
use App\Models\VariationVariant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the product.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('pages.admin.products.index');
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View|RedirectResponse
    {
        try {
            $this->authorize('create', Product::class);

            return view('pages.admin.products.create');
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menambahkan produk baru.');

            return redirect()->route('admin.products.index');
        }
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(array $validated)
    {
        try {
            $this->authorize('create', Product::class);

            $price = $validated['price'];
            $priceDiscount = $validated['priceDiscount'] ?? null;

            if ($validated['variation']['name'] !== '') {
                $variants = $validated['variation']['variants'];

                $minPriceVariant = collect($variants)->min('price');
                $price = $minPriceVariant;
                $priceDiscount = collect($variants)->firstWhere('price', $minPriceVariant)['priceDiscount'];
            }

            $product = Product::create([
                'subcategory_id' => $validated['subcategoryId'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'main_sku' => $validated['mainSku'],
                'base_price' => str_replace('.', '', $price),
                'base_price_discount' => $priceDiscount ? str_replace('.', '', $priceDiscount) : null,
                'is_active' => (bool) $validated['isActive'],
                'warranty' => $validated['warranty'],
                'material' => $validated['material'],
                'dimension' => $validated['length'].'x'.$validated['width'].'x'.$validated['height'],
                'package' => $validated['package'],
                'weight' => (int) str_replace('.', '', $validated['weight']),
                'power' => is_numeric($validated['power']) ? (int) str_replace('.', '', $validated['power']) : null,
                'voltage' => $validated['voltage'] !== '' ? $validated['voltage'] : null,
            ]);

            $thumbnailName = uniqid().'_'.microtime(true).'.'.$validated['thumbnail']->extension();

            $validated['thumbnail']->storeAs('product-images', $thumbnailName, 'public_uploads');

            $product->images()->create([
                'file_name' => $thumbnailName,
                'is_thumbnail' => true,
            ]);

            foreach ($validated['images'] as $image) {
                $fileName = uniqid().'_'.microtime(true).'.'.$image->extension();

                $image->storeAs('product-images', $fileName, 'public_uploads');

                $product->images()->create([
                    'file_name' => $fileName,
                    'is_thumbnail' => (bool) false,
                ]);
            }

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
                        'price_discount' => $variant['priceDiscount'] !== '' ? str_replace('.', '', $variant['priceDiscount']) : null,
                        'stock' => (int) str_replace('.', '', $variant['stock']),
                        'is_active' => (bool) $variant['isVariantActive'],
                    ]);

                    VariantCombination::create([
                        'product_variant_id' => $productVariant->id,
                        'variation_variant_id' => $variationVariant->id,
                    ]);
                }
            }
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menambahkan produk baru.');

            return redirect()->route('admin.products.index');
        }
    }

    /**
     * Display the specified product.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $product = Product::with(['images', 'subcategory.category'])->findBySlug($slug)->first();

        if (! $product) {
            session()->flash('error', 'Data produk tidak ditemukan.');

            return redirect()->route('admin.products.index');
        }

        try {
            $this->authorize('view', $product);

            return view('pages.admin.products.show', compact('product'));
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk melihat detail produk ini.');

            return redirect()->route('admin.products.index');
        }
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(string $slug)
    {
        $product = Product::with(['images', 'variants.combinations.variationVariant.variation'])->findBySlug($slug)->first();

        if (! $product) {
            session()->flash('error', 'Data produk tidak ditemukan.');

            return redirect()->route('admin.products.index');
        }

        try {
            $this->authorize('update', $product);

            return view('pages.admin.products.edit', compact('product'));
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mengubah produk ini.');

            return redirect()->route('admin.products.index');
        }
    }

    /**
     * Update the specified product in storage.
     */
    public function update(array $validated, Product $product)
    {
        try {
            $this->authorize('update', $product);

            $price = $validated['price'];
            $priceDiscount = $validated['priceDiscount'] ?? null;

            if ($validated['variation']['name'] !== '') {
                $variants = $validated['variation']['variants'];

                $minPriceVariant = collect($variants)->min('price');
                $price = $minPriceVariant;
                $priceDiscount = collect($variants)->firstWhere('price', $minPriceVariant)['priceDiscount'];
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
                'dimension' => $validated['length'].'x'.$validated['width'].'x'.$validated['height'],
                'package' => $validated['package'],
                'weight' => (int) str_replace('.', '', $validated['weight']),
                'power' => is_numeric($validated['power']) ? (int) str_replace('.', '', $validated['power']) : null,
                'voltage' => $validated['voltage'] !== '' ? $validated['voltage'] : null,
            ]);

            if ($validated['newThumbnail']) {
                $oldThumbnail = $product->images()->thumbnail()->first();

                if ($oldThumbnail) {
                    $filePath = 'product-images/'.$oldThumbnail->file_name;

                    if (Storage::disk('public_uploads')->exists($filePath)) {
                        Storage::disk('public_uploads')->delete($filePath);
                    }

                    $oldThumbnail->delete();
                }

                $thumbnailName = uniqid().'_'.microtime(true).'.'.$validated['newThumbnail']->extension();

                $validated['newThumbnail']->storeAs('product-images', $thumbnailName, 'public_uploads');

                $product->images()->create([
                    'file_name' => $thumbnailName,
                    'is_thumbnail' => true,
                ]);
            }

            if ($validated['newImages']) {
                foreach ($validated['newImages'] as $image) {
                    $fileName = uniqid().'_'.microtime(true).'.'.$image->extension();

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
                        'price_discount' => $variant['priceDiscount'] ? str_replace('.', '', $variant['priceDiscount']) : null,
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
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mengubah produk ini.');

            return redirect()->route('admin.products.index');
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        try {
            $this->authorize('delete', $product);

            $product->delete();

            session()->flash('success', 'Produk '.$product->name.' berhasil diarsip.');

            return redirect()->route('admin.products.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk mengarsip produk ini.');

            return redirect()->route('admin.products.index');
        }
    }

    /**
     * Remove the specified product image from storage.
     */
    public function destroyImage(ProductImage $image)
    {
        $product = $image->product;

        if ($product->images->count() <= 1) {
            session()->flash('error', 'Anda tidak dapat menghapus gambar produk ini, produk setidaknya harus memiliki satu gambar.');

            return redirect()->route('admin.products.edit', ['slug' => $product->slug]);
        }

        $filePath = 'product-images/'.$image->file_name;

        if (Storage::disk('public_uploads')->exists($filePath)) {
            Storage::disk('public_uploads')->delete($filePath);
        }

        $image->delete();

        session()->flash('success', 'Gambar produk berhasil dihapus.');

        return redirect()->route('admin.products.edit', ['slug' => $product->slug]);
    }

    /**
     * Display a listing of the archived product.
     */
    public function archive(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('pages.admin.products.archive');
    }

    /**
     * Restore the specified product in storage.
     */
    public function restore(string $id)
    {
        $product = Product::withTrashed()->find($id);

        if (! $product) {
            session()->flash('error', 'Data produk tidak ditemukan.');

            return redirect()->route('admin.archived-products.index');
        }

        try {
            $this->authorize('restore', $product);

            $product->restore();

            session()->flash('success', 'Produk '.$product->name.' berhasil dipulihkan.');

            return redirect()->route('admin.archived-products.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk memulihkan produk ini.');

            return redirect()->route('admin.archived-products.index');
        }
    }

    /**
     * Force remove the specified product from storage.
     */
    public function forceDelete(string $id)
    {
        $product = Product::withTrashed()->find($id);

        if (! $product) {
            session()->flash('error', 'Data produk tidak ditemukan.');

            return redirect()->route('admin.archived-products.index');
        }

        try {
            $this->authorize('forceDelete', $product);

            foreach ($product->images as $image) {
                $filePath = 'product-images/'.$image->file_name;

                if (Storage::disk('public_uploads')->exists($filePath)) {
                    Storage::disk('public_uploads')->delete($filePath);
                }
            }

            $product->forceDelete();

            session()->flash('success', 'Produk '.$product->name.' berhasil dihapus.');

            return redirect()->route('admin.archived-products.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menghapus produk ini.');

            return redirect()->route('admin.archived-products.index');
        }
    }
}
