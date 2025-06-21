<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $this->admin = $admin;
});

test('password can be updated', function () {
    $component = Volt::test('admin.profile.update-password-form')
        ->set('isEditing', true)
        ->set('form.user', $this->admin)
        ->set('form.current_password', 'password')
        ->set('form.password', 'NewPassword123')
        ->set('form.password_confirmation', 'NewPassword123')
        ->call('save');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/admin/pengaturan-akun');
});

test('correct password must be provided to update password', function () {
    $component = Volt::test('admin.profile.update-password-form')
        ->set('isEditing', true)
        ->set('form.user', $this->admin)
        ->set('form.current_password', 'WrongPassword123')
        ->set('form.password', 'NewPassword123')
        ->set('form.password_confirmation', 'NewPassword123')
        ->call('save');

    $component
        ->assertHasErrors(['form.current_password'])
        ->assertNoRedirect();
});
