<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);
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

    Storage::fake('public');

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
        // ->set('form.variation', '')
        ->call('save')
        ->assertHasNoErrors();

    // $this->assertDatabaseHas('products', [
    //     'subcategory_id' => $subcategory->id,
    //     'name' => 'subcategory test',
    // ]);
});

// test('subcategory can be updated', function () {
//     $admin = \App\Models\User::factory()->create();
//     $admin->assignRole('admin');

//     $this->actingAs($admin);

//     $category = \App\Models\Category::factory()->create();

//     $subcategory = \App\Models\Subcategory::factory()->create([
//         'category_id' => $category->id,
//     ]);

//     $response = $this->get('/admin/manajemen-subkategori/' . $subcategory->slug . '/ubah');

//     $response
//         ->assertOk()
//         ->assertSeeVolt('admin.subcategories.subcategory-edit-form');

//     $subcategory = (new \App\Models\Subcategory)->newFromBuilder(
//         \App\Models\Subcategory::queryBySlug(slug: $subcategory->slug, columns: [
//             'id',
//             'category_id',
//             'name',
//         ])
//     );

//     $newCategory = \App\Models\Category::factory()->create();

//     Volt::test('admin.subcategories.subcategory-edit-form', ['subcategory' => $subcategory])
//         ->set('form.categoryId', $newCategory->id)
//         ->set('form.name', 'subcategory test')
//         ->call('save')
//         ->assertHasNoErrors();

//     $subcategory->refresh();

//     $this->assertSame($newCategory->id, $subcategory->category_id);
//     $this->assertSame('subcategory test', $subcategory->name);
// });

// test('subcategory can be deleted', function () {
//     $admin = \App\Models\User::factory()->create();
//     $admin->assignRole('admin');

//     $this->actingAs($admin);

//     $category = \App\Models\Category::factory()->create();

//     $subcategory = \App\Models\Subcategory::factory()->create([
//         'category_id' => $category->id,
//     ]);

//     $response = $this->get('/admin/manajemen-subkategori');

//     $response
//         ->assertOk()
//         ->assertSeeVolt('admin.subcategories.subcategory-table');

//     Volt::test('admin.subcategories.subcategory-table')
//         ->call('delete', $subcategory->id)
//         ->assertHasNoErrors()
//         ->assertRedirect('/admin/manajemen-subkategori');

//     $this->assertDatabaseMissing('subcategories', [
//         'id' => $subcategory->id,
//     ]);
// });

// test('subcategory detail page accessible', function () {
//     $admin = \App\Models\User::factory()->create();
//     $admin->assignRole('admin');

//     $this->actingAs($admin);

//     $subcategory = \App\Models\Subcategory::factory()->create();

//     $response = $this->get('/admin/manajemen-subkategori/' . $subcategory->slug . '/detail');

//     $response
//         ->assertOk()
//         ->assertSee('Detail Subkategori');
// });