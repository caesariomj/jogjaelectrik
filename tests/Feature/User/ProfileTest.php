<?php

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    \Database\Factories\ProvinceFactory::resetCount();
    \Database\Factories\CityFactory::resetCount();
    \Database\Factories\DistrictFactory::resetCount();

    \App\Models\Province::factory()->count(4)->create();
    \App\Models\City::factory()->count(7)->create();
    \App\Models\District::factory()->count(7)->create();

    $user = User::factory()->create([
        'district_id' => '1',
        'phone_number' => Crypt::encryptString(ltrim('0811-1111-1111', '0')),
        'address' => Crypt::encryptString('Jl. Testing Alamat'),
        'postal_code' => Crypt::encryptString('12345'),
        'password' => Hash::make('Password1'),
    ]);

    $user->assignRole('user');

    $this->actingAs($user);

    $this->user = $user;
});

test('profile page is displayed', function () {
    $response = $this->get('/profil-saya');

    $response->assertRedirect('/konfirmasi-password');

    Volt::test('pages.auth.confirm-password')
        ->set('password', 'Password1')
        ->call('confirmPassword')
        ->assertHasNoErrors()
        ->assertRedirect('/profil-saya');

    $finalResponse = $this->get('/profil-saya');

    $finalResponse
        ->assertOk()
        ->assertSee('Profil Saya');
});

test('profile information can be updated', function () {
    session()->put('auth.password_confirmed_at', time());

    $component = Volt::test('user.profile.update-profile-information-form')
        ->set('isEditing', true)
        ->set('form.name', 'Test User')
        ->set('form.email', 'test@example.com')
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/profil-saya');

    $this->user->refresh();

    $this->assertSame('Test User', $this->user->name);
    $this->assertSame('test@example.com', $this->user->email);
    $this->assertNull($this->user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    session()->put('auth.password_confirmed_at', time());

    $component = Volt::test('user.profile.update-profile-information-form')
        ->set('isEditing', true)
        ->set('form.name', 'Test User')
        ->set('form.email', $this->user->email)
        ->call('updateProfileInformation');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/profil-saya');

    $this->assertNotNull($this->user->refresh()->email_verified_at);
});
