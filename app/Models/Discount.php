<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
        'max_discount_amount',
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
    public static function baseQuery(array $columns = ['*'])
    {
        return DB::table('discounts')->select($columns);
    }

    public static function queryById(string $id, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('discounts.id', $id);
    }

    public static function queryByCode(string $code, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('discounts.code', 'LIKE', '%'.$code.'%');
    }

    public static function queryAllUsable(?string $userId = null, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)
            ->where('discounts.is_active', true)
            ->where(function ($query) {
                $query->whereNull('discounts.start_date')->orWhere('discounts.start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('discounts.end_date')->orWhere('discounts.end_date', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('discounts.usage_limit')->orWhereColumn('discounts.usage_limit', '>', 'discounts.used_count');
            })
            ->when($userId, function ($query) use ($userId) {
                $query->whereNotExists(function ($subquery) use ($userId) {
                    $subquery->select(DB::raw(1))
                        ->from('order_discounts')
                        ->join('orders', 'orders.id', '=', 'order_discounts.order_id')
                        ->where('order_discounts.is_used', true)
                        ->where('orders.user_id', $userId)
                        ->whereNotNull('orders.id')
                        ->whereColumn('order_discounts.discount_id', 'discounts.id');
                });
            });
    }
}
