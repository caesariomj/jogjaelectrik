<?php

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    \Database\Factories\ProvinceFactory::resetCount();
    \Database\Factories\CityFactory::resetCount();

    \App\Models\Province::factory()->count(4)->create();
    \App\Models\City::factory()->count(7)->create();

    $admin = User::factory()->create([
        'city_id' => '1',
        'phone_number' => Crypt::encryptString(ltrim('0811-1111-1111', '0')),
        'address' => Crypt::encryptString('Jl. Testing Alamat'),
        'postal_code' => Crypt::encryptString('12345'),
        'password' => Hash::make('Password1'),
    ]);

    $admin->assignRole('admin');

    $this->actingAs($admin);

    $this->admin = $admin;
});

test('profile page is displayed', function () {
    $response = $this->get('admin/profil-saya');

    $response->assertRedirect('/konfirmasi-password');

    Volt::test('pages.auth.confirm-password')
        ->set('password', 'Password1')
        ->call('confirmPassword')
        ->assertHasNoErrors()
        ->assertRedirect('admin/profil-saya');

    $finalResponse = $this->get('admin/profil-saya');

    $finalResponse
        ->assertOk()
        ->assertSee('Profil Saya');
});

test('profile information can be updated', function () {
    session()->put('auth.password_confirmed_at', time());

    $component = Volt::test('admin.profile.update-profile-information-form')
        ->set('isEditing', true)
        ->set('form.name', 'Test Admin')
        ->set('form.email', 'test@example.com')
        ->call('save');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('admin/profil-saya');

    $this->admin->refresh();

    $this->assertSame('Test Admin', $this->admin->name);
    $this->assertSame('test@example.com', $this->admin->email);
    $this->assertNotNull($this->admin->email_verified_at);
});
