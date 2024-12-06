<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory, HasUuids;

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
        'name',
        'description',
        'code',
        'type',
        'value',
        'start_date',
        'end_date',
        'usage_limit',
        'used_count',
        'minimum_purchase',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Model relations.
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orderDiscounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
    }

    /**
     * Discount-related functions.
     */
    public function scopeFindByCode($query, string $code)
    {
        return $query->where('code', 'LIKE', "%{$code}%");
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUsable($query, $userId = null)
    {
        return $query->where(function ($query) {
            $query->whereNull('start_date')->orWhere('start_date', '<=', now());
        })
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')->orWhereColumn('usage_limit', '>', 'used_count');
            })
            ->whereDoesntHave('orderDiscounts', function ($query) use ($userId) {
                $query->where('is_used', true)
                    ->whereHas('order', function ($query) use ($userId) {
                        $query->where('user_id', $userId)
                            ->whereNotNull('id');
                    });
            });
    }

    public function isValid(float $cartTotal)
    {
        if (! is_null($this->minimum_purchase) && $cartTotal < $this->minimum_purchase) {
            return 'Diskon hanya berlaku untuk pembelian minimal Rp '.formatPrice($this->minimum_purchase).'.';
        }

        return true;
    }

    public function calculateDiscount(float $cartTotal): float
    {
        if ($this->type === 'fixed') {
            return min($this->value, $cartTotal);
        } elseif ($this->type === 'percentage') {
            return $cartTotal * ($this->value / 100);
        }

        return 0;
    }
}
