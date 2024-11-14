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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->foreignUuid('subcategory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('main_sku')->unique();
            $table->decimal('base_price', 10, 2);
            $table->decimal('base_price_discount', 10, 2)->nullable();
            $table->boolean('is_active');
            $table->string('warranty', 100);
            $table->string('material', 50);
            $table->string('dimension', 50);
            $table->string('package', 100);
            $table->unsignedSmallInteger('weight');
            $table->unsignedSmallInteger('power')->nullable();
            $table->unsignedSmallInteger('voltage')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->string('variant_sku')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('price_discount', 10, 2)->nullable();
            $table->unsignedSmallInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('variations', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('name', 50);
        });

        Schema::create('variation_variants', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->foreignUuid('variation_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
        });

        Schema::create('variant_combinations', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->foreignUuid('product_variant_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('variation_variant_id')->unique()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_combinations');
        Schema::dropIfExists('variation_variants');
        Schema::dropIfExists('variations');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
