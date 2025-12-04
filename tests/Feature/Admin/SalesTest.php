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

    $component = Volt::test('admin.sales.sale-table')
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

    Volt::test('admin.sales.sale-table')
        ->assertSee('Unduh Laporan');
});

test('sales report download button does not appear in the admin role', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Volt::test('admin.sales.sale-table')
        ->assertDontSee('Unduh Laporan');
});

test('input sales page accessible', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->get('/admin/penjualan/tambah');

    $response
        ->assertOk()
        ->assertSee('Input Penjualan');
});

test('input sales page can be used to input offline sales', function () {
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $product = \App\Models\Product::factory()->create();

    Volt::test('admin.sales.sale-create-form')
        ->set('form.transactionTime', '04-12-2025 21:55')
        ->set('form.source', 'offline')
        ->set('form.totalPrice', $product->base_price)
        ->set('form.items', [
            [
                'id' => $product->id,
                'name' => $product->name,
                'variants' => [
                    [
                        'id' => $product->variants->first()->id,
                        'price' => $product->variants->first()->price_discount ? $product->variants->first()->price_discount : $product->variants->first()->price,
                        'quantity' => 1,
                        'stock' => $product->variants->first()->stock,
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasErrors();
});
