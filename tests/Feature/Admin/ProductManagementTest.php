<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);
});

test('product management page accessible', function () {
    $response = $this->get('/admin/manajemen-produk');

    $response
        ->assertOk()
        ->assertSee('Manajemen Produk');
});

test('product can be created', function () {
    $response = $this->get('/admin/manajemen-produk/tambah');

    $category = \App\Models\Category::factory()->create();
    $subcategory = \App\Models\Subcategory::factory()->create([
        'category_id' => $category->id,
    ]);

    $response
        ->assertOk()
        ->assertSeeVolt('admin.products.product-create-form');

    Storage::fake('public_uploads');

    $thumbnail = UploadedFile::fake()->image('thumbnail.jpg');
    $images = [
        UploadedFile::fake()->image('image1.jpg'),
        UploadedFile::fake()->image('image2.jpg'),
    ];

    Volt::test('admin.products.product-create-form')
        ->set('form.thumbnail', $thumbnail)
        ->set('form.name', 'Testing Product')
        ->set('form.subcategoryId', $subcategory->id)
        ->set('form.mainSku', 'testing-product')
        ->set('form.costPrice', 40000)
        ->set('form.description', 'Testing Product Description')
        ->set('form.isActive', true)
        ->set('form.images', $images)
        ->set('form.warranty', '1 tahun garansi toko')
        ->set('form.material', 'Plastik')
        ->set('form.length', '100')
        ->set('form.width', '100')
        ->set('form.height', '100')
        ->set('form.weight', 1000)
        ->set('form.package', '1x unit')
        ->set('form.power', 100)
        ->set('form.voltage', '220-240')
        ->set('form.price', 100000)
        ->set('form.priceDiscount', 50000)
        ->set('form.stock', 100)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', [
        'subcategory_id' => $subcategory->id,
        'name' => 'Testing Product',
    ]);

    $product = \App\Models\Product::where('name', 'Testing Product')->firstOrFail();

    $thumbnailImage = $product->images()->where('is_thumbnail', true)->first();
    $this->assertNotNull($thumbnailImage);

    Storage::disk('public_uploads')->assertExists('product-images/'.$thumbnailImage->file_name);

    $nonThumbnailImages = $product->images()->where('is_thumbnail', false)->get();
    foreach ($nonThumbnailImages as $img) {
        Storage::disk('public_uploads')->assertExists('product-images/'.$img->file_name);
    }
});

test('product can be updated', function () {
    $category = \App\Models\Category::factory()->create();
    $subcategory = \App\Models\Subcategory::factory()->create([
        'category_id' => $category->id,
    ]);

    $product = \App\Models\Product::factory()->create([
        'subcategory_id' => $subcategory->id,
    ]);

    $response = $this->get('/admin/manajemen-produk/'.$product->slug.'/ubah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.products.product-edit-form');

    Storage::fake('public_uploads');

    $product = (new \App\Models\Product)->newFromBuilder(
        \App\Models\Product::queryBySlug(slug: $product->slug, columns: [
            'products.id',
            'products.subcategory_id',
            'products.name',
            'products.slug',
            'products.description',
            'products.main_sku',
            'products.cost_price',
            'products.base_price',
            'products.base_price_discount',
            'products.is_active',
            'products.warranty',
            'products.material',
            'products.dimension',
            'products.package',
            'products.weight',
            'products.power',
            'products.voltage',
        ], relations: [
            'category',
            'images',
            'variation',
            'aggregates',
        ])
    );

    foreach ($product->images as $img) {
        Storage::disk('public_uploads')->put('product-images/'.$img->file_name, 'from debug test');
    }

    $newSubcategory = \App\Models\Subcategory::factory()->create([
        'category_id' => $category->id,
    ]);

    $thumbnail = UploadedFile::fake()->image('thumbnail.jpg');
    $images = [
        UploadedFile::fake()->image('image1.jpg'),
        UploadedFile::fake()->image('image2.jpg'),
    ];

    Volt::test('admin.products.product-edit-form', ['product' => $product])
        ->set('form.newThumbnail', $thumbnail)
        ->set('form.name', 'Testing Product')
        ->set('form.subcategoryId', $newSubcategory->id)
        ->set('form.mainSku', 'testing-product')
        ->set('form.costPrice', 40000)
        ->set('form.description', 'Testing Product Description')
        ->set('form.isActive', true)
        ->set('form.newImages', $images)
        ->set('form.warranty', '1 tahun garansi toko')
        ->set('form.material', 'Plastik')
        ->set('form.length', '100')
        ->set('form.width', '100')
        ->set('form.height', '100')
        ->set('form.weight', 1000)
        ->set('form.package', '1x unit')
        ->set('form.power', 100)
        ->set('form.voltage', '220-240')
        ->set('form.price', 100000)
        ->set('form.priceDiscount', 50000)
        ->set('form.stock', 100)
        ->call('removeVariation')
        ->call('save')
        ->assertHasNoErrors();

    $product->refresh();

    $this->assertSame($newSubcategory->id, $product->subcategory_id);
    $this->assertSame('Testing Product', $product->name);

    $thumbnailImage = $product->images()->where('is_thumbnail', true)->first();
    $this->assertNotNull($thumbnailImage);

    Storage::disk('public_uploads')->assertExists('product-images/'.$thumbnailImage->file_name);

    $nonThumbnailImages = $product->images()->where('is_thumbnail', false)->get();
    foreach ($nonThumbnailImages as $img) {
        Storage::disk('public_uploads')->assertExists('product-images/'.$img->file_name);
    }
});

test('product can be archived', function () {
    $category = \App\Models\Category::factory()->create();
    $subcategory = \App\Models\Subcategory::factory()->create([
        'category_id' => $category->id,
    ]);

    $product = \App\Models\Product::factory()->create([
        'subcategory_id' => $subcategory->id,
    ]);

    $response = $this->get('/admin/manajemen-produk');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.products.product-table');

    Volt::test('admin.products.product-table')
        ->call('archive', $product->id)
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-produk');

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('product can be permanently deleted by super_admin role', function () {
    $category = \App\Models\Category::factory()->create();
    $subcategory = \App\Models\Subcategory::factory()->create([
        'category_id' => $category->id,
    ]);

    $product = \App\Models\Product::factory()->create([
        'subcategory_id' => $subcategory->id,
        'deleted_at' => now(),
    ]);

    $response = $this->get('/admin/manajemen-arsip-produk');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.products.product-table');

    Volt::test('admin.products.product-table', ['archived' => true])
        ->call('delete', $product->id)
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-arsip-produk');

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

test('product detail page accessible', function () {
    $category = \App\Models\Category::factory()->create();
    $subcategory = \App\Models\Subcategory::factory()->create([
        'category_id' => $category->id,
    ]);

    $product = \App\Models\Product::factory()->create([
        'subcategory_id' => $subcategory->id,
    ]);

    $response = $this->get('/admin/manajemen-produk/'.$product->slug.'/detail');

    $response
        ->assertOk()
        ->assertSee('Detail Produk');
});
