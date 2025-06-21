<?php

use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();
});

test('category management page accessible', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->get('/admin/manajemen-kategori');

    $response
        ->assertOk()
        ->assertSee('Manajemen Kategori');
});

test('category can be created', function () {
    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);

    $response = $this->get('/admin/manajemen-kategori/tambah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.categories.category-create-form');

    Volt::test('admin.categories.category-create-form')
        ->set('form.name', 'Category Test')
        ->set('form.isPrimary', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'Category Test',
        'is_primary' => true,
    ]);
});

test('is_primary category input does not appear in admin role', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->get('/admin/manajemen-kategori/tambah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.categories.category-create-form');

    Volt::test('admin.categories.category-create-form')
        ->assertDontSee('Kategori Utama');
});

test('category can be updated', function () {
    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);

    $category = \App\Models\Category::factory()->create([
        'is_primary' => true,
    ]);

    $response = $this->get('/admin/manajemen-kategori/' . $category->slug . '/ubah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.categories.category-edit-form');

    $category = (new \App\Models\Category)->newFromBuilder(
        \App\Models\Category::queryBySlug(slug: $category->slug, columns: [
            'id',
            'name',
            'is_primary',
        ])
    );

    Volt::test('admin.categories.category-edit-form', ['category' => $category])
        ->set('form.name', 'category test')
        ->set('form.isPrimary', false)
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();

    $this->assertSame('category test', $category->name);
    $this->assertSame(false, $category->is_primary);
});

test('category can be deleted', function () {
    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);

    $category = \App\Models\Category::factory()->create();

    $response = $this->get('/admin/manajemen-kategori');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.categories.category-table');

    Volt::test('admin.categories.category-table')
        ->call('delete', $category->id)
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-kategori');

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('category detail page accessible', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $category = \App\Models\Category::factory()->create();

    $response = $this->get('/admin/manajemen-kategori/' . $category->slug . '/detail');

    $response
        ->assertOk()
        ->assertSee('Detail Kategori');
});