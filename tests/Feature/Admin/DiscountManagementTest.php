<?php

use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    $superAdmin = \App\Models\User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->actingAs($superAdmin);
});

test('discount management page accessible', function () {
    $response = $this->get('/admin/manajemen-diskon');

    $response
        ->assertOk()
        ->assertSee('Manajemen Diskon');
});

test('discount can be created', function () {
    $response = $this->get('/admin/manajemen-diskon/tambah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.discounts.discount-create-form');

    Volt::test('admin.discounts.discount-create-form')
        ->set('form.name', 'discount test')
        ->set('form.description', 'discount description test')
        ->set('form.isActive', true)
        ->set('form.code', 'discount-test')
        ->set('form.type', 'percentage')
        ->set('form.value', 10)
        ->set('form.maxDiscountAmount', 100000)
        ->set('form.usageLimit', 10)
        ->set('form.minimumPurchase', 500000)
        ->set('form.startDate', '21-06-2025')
        ->set('form.endDate', '22-06-2025')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('discounts', [
        'name' => 'discount test',
    ]);
});

test('discount can be updated', function () {
    $discount = \App\Models\Discount::factory()->create();

    $response = $this->get('/admin/manajemen-diskon/' . $discount->code . '/ubah');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.discounts.discount-edit-form');

    $discount = (new \App\Models\Discount)->newFromBuilder(
        \App\Models\Discount::queryByCode(code: $discount->code, columns: [
            'discounts.id',
            'discounts.name',
            'discounts.description',
            'discounts.code',
            'discounts.type',
            'discounts.value',
            'discounts.start_date',
            'discounts.end_date',
            'discounts.usage_limit',
            'discounts.used_count',
            'discounts.max_discount_amount',
            'discounts.minimum_purchase',
            'discounts.is_active',
        ])->first()
    );

    Volt::test('admin.discounts.discount-edit-form', ['discount' => $discount])
        ->set('form.name', 'discount test')
        ->set('form.description', 'discount description test')
        ->set('form.isActive', true)
        ->set('form.code', 'discount-test')
        ->set('form.type', 'fixed')
        ->set('form.value', 50000)
        ->set('form.usageLimit', 10)
        ->set('form.minimumPurchase', 500000)
        ->set('form.startDate', '21-06-2025')
        ->set('form.endDate', '22-06-2025')
        ->call('save')
        ->assertHasNoErrors();

    $discount->refresh();

    $this->assertSame('discount test', $discount->name);
});

test('discount can be deleted', function () {
    $discount = \App\Models\Discount::factory()->create();

    $response = $this->get('/admin/manajemen-diskon');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.discounts.discount-table');

    Volt::test('admin.discounts.discount-table')
        ->call('delete', $discount->id)
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-diskon');

    $this->assertDatabaseMissing('discounts', [
        'id' => $discount->id,
    ]);
});

test('discount usage can be reseted', function () {
    $discount = \App\Models\Discount::factory()->create([
        'used_count' => 4
    ]);

    $response = $this->get('/admin/manajemen-diskon');

    $response
        ->assertOk()
        ->assertSeeVolt('admin.discounts.discount-table');

    Volt::test('admin.discounts.discount-table')
        ->call('resetUsage', $discount->id)
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-diskon');

    $discount->refresh();

    $this->assertSame(0, $discount->used_count);
});

test('discount detail page accessible', function () {
    $discount = \App\Models\Discount::factory()->create();

    $response = $this->get('/admin/manajemen-diskon/' . $discount->code . '/detail');

    $response
        ->assertOk()
        ->assertSee('Detail Diskon');
});