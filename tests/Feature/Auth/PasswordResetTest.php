<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Volt;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/lupa-password');

    $response
        ->assertSeeVolt('pages.auth.forgot-password')
        ->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, \App\Notifications\ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, \App\Notifications\ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response
            ->assertSeeVolt('pages.auth.reset-password')
            ->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    $user = User::factory()->create();

    // Create a password reset token directly
    $token = Password::broker()->createToken($user);

    $component = Volt::test('pages.auth.reset-password', [
        'token' => $token,
        'email' => $user->email,
    ])
        ->set('form.email', $user->email)
        ->set('form.password', 'NewPassword123!')
        ->set('form.password_confirmation', 'NewPassword123!')
        ->set('form.token', $token);

    $component->call('resetPassword');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
});
