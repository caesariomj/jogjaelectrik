<?php

use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    $user = \App\Models\User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);

    $this->user = $user;
});

test('order management page accessible', function () {
    $response = $this->get('/pesanan');

    $response
        ->assertOk()
        ->assertSee('Pesanan Saya')
        ->assertSeeVolt('user.order-item-list');
});

test('shipped order can be completed', function () {
    $order = \App\Models\Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'shipping',
    ]);

    Volt::test('user.order-item-list')
        ->call('finishOrder', $order->id)
        ->assertHasNoErrors()
        ->assertRedirect('/pesanan');

    $order->refresh();

    $this->assertSame('completed', $order->status);
});

test('order detail page accessible', function () {
    $order = \App\Models\Order::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->get('/pesanan/'.$order->order_number.'/detail');

    $response
        ->assertOk()
        ->assertSee('Detail Pesanan');
});
