<?php

use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();
});

test('admin management page accessible', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->get('/admin/manajemen-admin');

    $response
        ->assertOk()
        ->assertSee('Penjualan');
});

test('admin management add button does not appear in admin role', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Volt::test('admin.admins.admin-table')
        ->assertDontSee('Tambah');
});

test('admin can be created by super_admin', function () {
    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);

    $response = $this->get('/admin/manajemen-admin/tambah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.admins.admin-create-form');

    Volt::test('admin.admins.admin-create-form')
        ->set('form.name', 'Test Admin')
        ->set('form.email', 'admin@example.com')
        ->set('form.role', 'admin')
        ->set('form.password', 'Password1')
        ->set('form.passwordConfirmation', 'Password1')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
    ]);
});

test('admin can be updated by super_admin', function () {
    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);

    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->get('/admin/manajemen-admin/'.$admin->id.'/ubah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.admins.admin-edit-form');

    $admin = (new \App\Models\User)->newFromBuilder(
        \App\Models\User::queryAdminById(id: $admin->id, columns: ['users.id', 'users.name', 'users.email'])->first()
    );

    Volt::test('admin.admins.admin-edit-form', ['admin' => $admin])
        ->set('form.name', 'Test Admin')
        ->set('form.email', 'admin@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $admin->refresh();

    $this->assertSame('Test Admin', $admin->name);
    $this->assertSame('admin@example.com', $admin->email);
});

test('admin can be deleted by super_admin', function () {
    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);

    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->get('/admin/manajemen-admin');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.admins.admin-table');

    Volt::test('admin.admins.admin-table')
        ->call('delete', $admin->id)
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-admin');

    $this->assertDatabaseMissing('users', [
        'id' => $admin->id,
    ]);
});

test('admin detail page accessible', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->get('/admin/manajemen-admin/'.$admin->id.'/detail');

    $response
        ->assertOk()
        ->assertSee('Detail Admin');
});
