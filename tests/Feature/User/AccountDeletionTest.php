<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

test('account can be deleted', function () {
    seedPermissionsAndRoles();

    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $user->assignRole('user');

    $this->actingAs($user);

    $component = Volt::test('user.profile.delete-user-form')
        ->set('form.password', 'password')
        ->call('deleteUser');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});
