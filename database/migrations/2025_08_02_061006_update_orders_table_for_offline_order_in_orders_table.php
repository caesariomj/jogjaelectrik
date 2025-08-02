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
            $table->uuid('user_id')->constrained('users')->cascadeOnDelete()->nullable()->change();
            $table->enum('source', ['offline', 'ecommerce'])->default('ecommerce')->after('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('user_id')->constrained('users')->cascadeOnDelete()->nullable(false)->change();
            $table->dropColumn('source');
        });
    }
};
