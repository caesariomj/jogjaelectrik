<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ProductReview extends Model
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
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_detail_id',
        'rating',
        'review',
    ];

    /**
     * Model relations.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productVariant(): HasOneThrough
    {
        return $this->hasOneThrough(ProductVariant::class, OrderDetail::class, 'id', 'id', 'order_detail_id', 'product_variant_id');
    }

    public function orderDetail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class);
    }
}
