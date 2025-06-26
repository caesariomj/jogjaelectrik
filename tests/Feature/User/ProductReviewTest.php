<?php

use Livewire\Volt\Volt;

beforeEach(function () {
    seedPermissionsAndRoles();

    $user = \App\Models\User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user);

    $this->user = $user;
});

test('user can rate products that have been purchased', function () {
    $order = \App\Models\Order::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
    ]);

    $order->payment->update([
        'status' => 'paid',
    ]);

    $reviews = [];

    foreach ($order->details as $key => $detail) {
        $reviews[] = [
            'order_detail_id' => $detail->id,
            'rating' => 5,
            'review' => 'Test product review '.$key + 1,
        ];
    }

    $response = $this->get('/pesanan');

    $response
        ->assertOk()
        ->assertSeeVolt('user.order-item-list');

    Volt::test('user.order-item-list')
        ->call('rateProducts', $reviews)
        ->assertHasNoErrors()
        ->assertRedirect('/pesanan');

    foreach ($reviews as $review) {
        $this->assertDatabaseHas('product_reviews', [
            'user_id' => $this->user->id,
            'order_detail_id' => $review['order_detail_id'],
            'rating' => $review['rating'],
            'review' => $review['review'],
        ]);
    }
});
