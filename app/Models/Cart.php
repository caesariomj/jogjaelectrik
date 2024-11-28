<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Cart extends Model
{
    use HasUuids;

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Primary key ID data type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'discount_id',
    ];

    /**
     * Model relations.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Cart-related functions.
     */
    public function scopeFindBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function calculateTotalPrice()
    {
        return $this->items()
            ->join('product_variants', 'cart_items.product_variant_id', '=', 'product_variants.id')
            ->sum(DB::raw('cart_items.quantity * product_variants.price'));
    }

    public function calculateTotalWeight()
    {
        return $this->items()
            ->join('product_variants', 'cart_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->sum(DB::raw('cart_items.quantity * products.weight'));
    }
}
