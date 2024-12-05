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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->uuid('token');
            $table->string('method', 50);
            $table->enum('status', ['pending', 'settlement', 'deny', 'cancel', 'expire', 'failure', 'refund'])->default('pending');
            $table->string('reference_number', 512)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
