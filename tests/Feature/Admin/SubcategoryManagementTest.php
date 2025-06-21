<?php

use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();
});

test('subcategory management page accessible', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->get('/admin/manajemen-subkategori');

    $response
        ->assertOk()
        ->assertSee('Manajemen Subkategori');
});

test('subcategory can be created', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->get('/admin/manajemen-subkategori/tambah');

    $category = \App\Models\Category::factory()->create();

    $response
        ->assertOk()
        ->assertSeeVolt('admin.subcategories.subcategory-create-form');

    Volt::test('admin.subcategories.subcategory-create-form')
        ->set('form.categoryId', $category->id)
        ->set('form.name', 'subcategory test')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('subcategories', [
        'category_id' => $category->id,
        'name' => 'subcategory test',
    ]);
});

test('subcategory can be updated', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $category = \App\Models\Category::factory()->create();

    $subcategory = \App\Models\Subcategory::factory()->create([
        'category_id' => $category->id,
    ]);

    $response = $this->get('/admin/manajemen-subkategori/' . $subcategory->slug . '/ubah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.subcategories.subcategory-edit-form');

    $subcategory = (new \App\Models\Subcategory)->newFromBuilder(
        \App\Models\Subcategory::queryBySlug(slug: $subcategory->slug, columns: [
            'id',
            'category_id',
            'name',
        ])
    );

    $newCategory = \App\Models\Category::factory()->create();

    Volt::test('admin.subcategories.subcategory-edit-form', ['subcategory' => $subcategory])
        ->set('form.categoryId', $newCategory->id)
        ->set('form.name', 'subcategory test')
        ->call('save')
        ->assertHasNoErrors();

    $subcategory->refresh();

    $this->assertSame($newCategory->id, $subcategory->category_id);
    $this->assertSame('subcategory test', $subcategory->name);
});

test('subcategory can be deleted', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $category = \App\Models\Category::factory()->create();

    $subcategory = \App\Models\Subcategory::factory()->create([
        'category_id' => $category->id,
    ]);

    $response = $this->get('/admin/manajemen-subkategori');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.subcategories.subcategory-table');

    Volt::test('admin.subcategories.subcategory-table')
        ->call('delete', $subcategory->id)
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-subkategori');

    $this->assertDatabaseMissing('subcategories', [
        'id' => $subcategory->id,
    ]);
});

test('subcategory detail page accessible', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $subcategory = \App\Models\Subcategory::factory()->create();

    $response = $this->get('/admin/manajemen-subkategori/' . $subcategory->slug . '/detail');

    $response
        ->assertOk()
        ->assertSee('Detail Subkategori');
});