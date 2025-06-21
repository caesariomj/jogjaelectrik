<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('login screen can be rendered', function () {
    $response = $this->get('/masuk');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('users can authenticate using the login screen', function () {
    seedPermissionsAndRoles();

    $user = User::factory()->create();
    $user->assignRole('user');

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component
        ->assertHasErrors()
        ->assertNoRedirect();

    $this->assertGuest();
});

test('navigation menu can be rendered', function () {
    seedPermissionsAndRoles();

    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);

    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSeeVolt('layout.user.navigation');
});

test('users can logout', function () {
    seedPermissionsAndRoles();

    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);

    $component = Volt::test('layout.user.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
});
