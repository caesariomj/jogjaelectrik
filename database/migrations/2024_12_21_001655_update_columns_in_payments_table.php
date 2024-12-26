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
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->dropColumn('status');

            $table->string('invoice_url', 512)->after('order_id');
            $table->enum('status', ['unpaid', 'paid', 'settled', 'expired', 'refunded'])->default('unpaid')->after('method');
            $table->timestamp('paid_at')->nullable()->after('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('invoice_url');
            $table->dropColumn('status');
            $table->dropColumn('paid_at');

            $table->uuid('token')->after('order_id');
            $table->enum('status', ['pending', 'settlement', 'deny', 'cancel', 'expire', 'failure', 'refund'])->default('pending')->after('method');
        });
    }
};
