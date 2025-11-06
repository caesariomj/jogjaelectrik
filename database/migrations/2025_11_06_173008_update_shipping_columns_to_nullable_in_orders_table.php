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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('shipping_address')->nullable()->change();
            $table->string('shipping_courier', 50)->nullable()->change();
            $table->unsignedTinyInteger('estimated_shipping_min_days')->nullable()->change();
            $table->unsignedTinyInteger('estimated_shipping_max_days')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('shipping_address')->nullable(false)->change();
            $table->string('shipping_courier', 50)->nullable(false)->change();
            $table->unsignedTinyInteger('estimated_shipping_min_days')->nullable(false)->change();
            $table->unsignedTinyInteger('estimated_shipping_max_days')->nullable(false)->change();
        });
    }
};
