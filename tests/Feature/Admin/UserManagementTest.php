<?php

use Database\Factories\CityFactory;
use Database\Factories\ProvinceFactory;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);
});

test('user management page accessible', function () {
    $response = $this->get('/admin/manajemen-pelanggan');

    $response
        ->assertOk()
        ->assertSee('Manajemen Pelanggan');
});

test('user can be edited by super_admin', function () {
    ProvinceFactory::resetCount();
    CityFactory::resetCount();

    \App\Models\Province::factory()->count(4)->create();
    \App\Models\City::factory()->count(7)->create();

    $user = \App\Models\User::factory()->create([
        'city_id' => '1',
        'phone_number' => \Illuminate\Support\Facades\Crypt::encryptString(ltrim('0811-1111-1111', '0')),
        'address' => \Illuminate\Support\Facades\Crypt::encryptString('Jl. Testing Alamat'),
        'postal_code' => \Illuminate\Support\Facades\Crypt::encryptString('12345'),
        'password' => \Illuminate\Support\Facades\Hash::make('Password1'),
    ]);
    $user->assignRole('user');

    $response = $this->get('/admin/manajemen-pelanggan/'.$user->id.'/ubah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.users.user-edit-form');

    $user = (new \App\Models\User)->newFromBuilder(
        \App\Models\User::queryById(id: $user->id, columns: [
            'users.id',
            'users.city_id',
            'users.name',
            'users.email',
            'users.password',
            'users.phone_number',
            'users.address',
            'users.postal_code',
        ])->first()
    );

    Volt::test('admin.users.user-edit-form', ['user' => $user])
        ->set('form.name', 'Test User')
        ->set('form.email', 'user@example.com')
        ->set('form.province', '2')
        ->set('form.city', '2')
        ->set('form.password', 'Password1')
        ->set('form.passwordConfirmation', 'Password1')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('user@example.com', $user->email);
});

test('user can be deleted by super_admin', function () {
    $user = \App\Models\User::factory()->create();
    $user->assignRole('user');

    $response = $this->get('/admin/manajemen-pelanggan');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.users.user-table');

    Volt::test('admin.users.user-table')
        ->call('delete', $user->id)
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-pelanggan');

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('user detail page accessible', function () {
    ProvinceFactory::resetCount();
    CityFactory::resetCount();

    \App\Models\Province::factory()->count(4)->create();
    \App\Models\City::factory()->count(7)->create();

    $user = \App\Models\User::factory()->create([
        'city_id' => '1',
        'phone_number' => \Illuminate\Support\Facades\Crypt::encryptString(ltrim('0811-1111-1111', '0')),
        'address' => \Illuminate\Support\Facades\Crypt::encryptString('Jl. Testing Alamat'),
        'postal_code' => \Illuminate\Support\Facades\Crypt::encryptString('12345'),
        'password' => \Illuminate\Support\Facades\Hash::make('Password1'),
    ]);
    $user->assignRole('user');

    $response = $this->get('/admin/manajemen-pelanggan/'.$user->id.'/detail');

    $response
        ->assertOk()
        ->assertSee('Detail Pelanggan');
});
