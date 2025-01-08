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
        Schema::table('refunds', function (Blueprint $table) {
            $table->string('xendit_refund_id', 50)->nullable()->after('payment_id');

            $table->enum('status', ['pending', 'approved', 'rejected', 'succeeded', 'failed'])->default('pending')->change();

            $table->string('rejection_reason')->nullable()->after('status');

            $table->timestamp('approved_at')->nullable()->after('rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn('xendit_refund_id');

            $table->enum('status', ['pending', 'succeeded', 'failed'])->default('pending')->change();

            $table->dropColumn('rejection_reason');

            $table->dropColumn('approved_at');
        });
    }
};
