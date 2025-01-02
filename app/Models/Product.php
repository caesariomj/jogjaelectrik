<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Primary key ID data type.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subcategory_id',
        'name',
        'slug',
        'description',
        'main_sku',
        'base_price',
        'base_price_discount',
        'is_active',
        'warranty',
        'material',
        'dimension',
        'package',
        'weight',
        'power',
        'voltage',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Model relations.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants(): HasOneOrMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderDetails(): HasManyThrough
    {
        return $this->hasManyThrough(OrderDetail::class, ProductVariant::class, 'product_id', 'product_variant_id', 'id', 'id');
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(ProductReview::class, ProductVariant::class, 'product_id', 'product_variant_id', 'id', 'id');
    }

    /**
     * Product-related functions.
     */
    public function scopeFindBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTotalStock()
    {
        return $this->variants()->sum('stock');
    }

    /**
     * The "booted" method of the model.
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            $product->slug = Str::slug($product->name);
        });

        static::updating(function ($product) {
            $product->slug = Str::slug($product->name);
        });
    }
}
