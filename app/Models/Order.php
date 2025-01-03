<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
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
        'order_number',
        'status',
        'shipping_address',
        'shipping_courier',
        'estimated_shipping_min_days',
        'estimated_shipping_max_days',
        'shipment_tracking_number',
        'note',
        'subtotal_amount',
        'discount_amount',
        'shipping_cost_amount',
        'total_amount',
        'cancelation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Model relations.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Order-related functions.
     */
    public function scopeFindByOrderNumber($query, string $orderNumber)
    {
        return $query->where('order_number', $orderNumber);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'waiting_payment')->where('created_at', '<=', \Carbon\Carbon::now()->subDay());
    }

    private static function generateOrderNumber()
    {
        $date = now()->format('Ymd');

        $uuid = (string) Str::uuid();

        $shortUuid = substr($uuid, 0, 6);

        return 'ORDR-'.$date.'-'.$shortUuid;
    }

    public function hasBeenReviewed()
    {
        return $this->details()->whereHas('productReview')->exists();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_number = self::generateOrderNumber();
        });
    }
}
