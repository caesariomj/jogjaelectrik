<?php

namespace Tests\Feature\Auth;

use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/daftar');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('new users can register', function () {
    seedPermissionsAndRoles();

    $component = Volt::test('pages.auth.register')
        ->set('form.name', 'Test User')
        ->set('form.email', 'test@example.com')
        ->set('form.password', 'Password1')
        ->set('form.password_confirmation', 'Password1')
        ->set('form.accept_terms_and_conditions', true);

    $component->call('register');

    $component->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
});
