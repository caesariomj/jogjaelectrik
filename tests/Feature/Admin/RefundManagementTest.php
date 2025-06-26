<?php

beforeEach(function () {
    seedPermissionsAndRoles();

    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);
});

test('refund management page accessible', function () {
    $response = $this->get('/admin/permintaan-refund');

    $response
        ->assertOk()
        ->assertSee('Permintaan Refund')
        ->assertSeeVolt('admin.refunds.refund-table');
});
