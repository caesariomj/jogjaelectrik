<?php

use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();
});

test('sales page accessible', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->get('/admin/penjualan');

    $response
        ->assertOk()
        ->assertSee('Penjualan');
});

test('sales report filter can be changed', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    $component = Volt::test('admin.reports.sales-table')
        ->set('month', 1)
        ->set('year', 2026);

    $component
        ->assertSet('month', 1)
        ->assertSet('year', 2026);
});

test('sales report download button appears in the super_admin role', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    Volt::test('admin.reports.sales-table')
        ->assertSee('Unduh Laporan');
});

test('sales report download button does not appear in the admin role', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Volt::test('admin.reports.sales-table')
        ->assertDontSee('Unduh Laporan');
});