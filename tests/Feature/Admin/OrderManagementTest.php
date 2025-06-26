<?php

use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);
});

test('order management page accessible', function () {
    $response = $this->get('/admin/manajemen-pesanan');

    $response
        ->assertOk()
        ->assertSee('Manajemen Pesanan')
        ->assertSeeVolt('admin.orders.order-table');
});

test('unpaid order can be canceled', function () {
    $order = \App\Models\Order::factory()->create([
        'status' => 'waiting_payment',
    ]);

    Volt::test('admin.orders.order-table')
        ->call('confirmCancelOrder', $order->id)
        ->set('cancelationReason', 'Test order cancelation reason')
        ->call('cancelOrder')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-pesanan');

    $order->refresh();

    $this->assertSame('canceled', $order->status);
    $this->assertSame('expired', $order->payment->status);
});

test('paid order can be canceled', function () {
    $order = \App\Models\Order::factory()->create([
        'status' => 'payment_received',
    ]);

    Volt::test('admin.orders.order-table')
        ->call('confirmCancelOrder', $order->id)
        ->set('cancelationReason', 'Test order cancelation reason')
        ->call('cancelOrder')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-pesanan');

    $order->refresh();

    $this->assertSame('canceled', $order->status);
    $this->assertSame('refunded', $order->payment->status);
});

test('paid order can be processed', function () {
    $order = \App\Models\Order::factory()->create([
        'status' => 'payment_received',
    ]);

    Volt::test('admin.orders.order-table')
        ->call('processOrder', $order->id)
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-pesanan');

    $order->refresh();

    $this->assertSame('processing', $order->status);
});

test('processed order can be canceled', function () {
    $order = \App\Models\Order::factory()->create([
        'status' => 'processing',
    ]);

    Volt::test('admin.orders.order-table')
        ->call('confirmCancelOrder', $order->id)
        ->set('cancelationReason', 'Test order cancelation reason')
        ->call('cancelOrder')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-pesanan');

    $order->refresh();

    $this->assertSame('canceled', $order->status);
    $this->assertSame('refunded', $order->payment->status);
});

test('processed order can be shipped', function () {
    $order = \App\Models\Order::factory()->create([
        'status' => 'processing',
    ]);

    Volt::test('admin.orders.order-table')
        ->call('confirmShipOrder', $order->id)
        ->set('shipmentTrackingNumber', 'TEST-TRACKING-NUMBER')
        ->call('shipOrder')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-pesanan');

    $order->refresh();

    $this->assertSame('shipping', $order->status);
    $this->assertSame('TEST-TRACKING-NUMBER', $order->shipment_tracking_number);
});

test('shipped order can be completed', function () {
    $order = \App\Models\Order::factory()->create([
        'status' => 'shipping',
    ]);

    Volt::test('admin.orders.order-table')
        ->call('confirmFinishOrder', $order->id)
        ->call('finishOrder')
        ->assertHasNoErrors()
        ->assertRedirect('/admin/manajemen-pesanan');

    $order->refresh();

    $this->assertSame('completed', $order->status);
});

test('order detail page accessible', function () {
    $order = \App\Models\Order::factory()->create();

    $response = $this->get('/admin/manajemen-pesanan/'.$order->order_number.'/detail');

    $response
        ->assertOk()
        ->assertSee('Detail Pesanan');
});
