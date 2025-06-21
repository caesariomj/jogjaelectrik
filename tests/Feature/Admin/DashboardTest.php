<?php

beforeEach(function () {
    seedPermissionsAndRoles();
});

test('dashboard page accessible by admin & super_admin role', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->get('/admin/dashboard');

    $response
        ->assertOk()
        ->assertSee('Dashboard');
});

test('dashboard page cannot be accessed by the user role', function () {
    $user = \App\Models\User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);

    $response = $this->get('/admin/dashboard');

    $response->assertStatus(404);
});
