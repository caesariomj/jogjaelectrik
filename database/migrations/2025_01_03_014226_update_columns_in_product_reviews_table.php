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
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');

            $table->foreignUuid('user_id')->after('id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->after('user_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');

            $table->foreignUuid('user_id')->unique()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->unique()->after('user_id')->constrained()->cascadeOnDelete();
        });
    }
};
