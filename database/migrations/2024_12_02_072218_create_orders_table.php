<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 50)->unique();
            $table->enum('status', ['waiting_payment', 'payment_received', 'processing', 'shipping', 'completed', 'failed', 'canceled'])->default('waiting_payment');
            $table->text('shipping_address');
            $table->string('shipping_courier', 50);
            $table->unsignedTinyInteger('estimated_shipping_min_days');
            $table->unsignedTinyInteger('estimated_shipping_max_days');
            $table->string('shipment_tracking_number', 50)->nullable();
            $table->text('note')->nullable();
            $table->decimal('subtotal_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->decimal('shipping_cost_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('cancelation_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
