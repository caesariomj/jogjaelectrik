<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();
});

test('password can be updated', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);

    $component = Volt::test('user.profile.update-password-form')
        ->set('isEditing', true)
        ->set('form.user', $user)
        ->set('form.current_password', 'password')
        ->set('form.password', 'NewPassword123')
        ->set('form.password_confirmation', 'NewPassword123')
        ->call('updatePassword');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/pengaturan-akun');
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);

    $component = Volt::test('user.profile.update-password-form')
        ->set('isEditing', true)
        ->set('form.user', $user)
        ->set('form.current_password', 'WrongPassword123')
        ->set('form.password', 'NewPassword123')
        ->set('form.password_confirmation', 'NewPassword123')
        ->call('updatePassword');

    $component
        ->assertHasErrors(['form.current_password'])
        ->assertNoRedirect();
});
