<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Payment extends Model
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
        'order_id',
        'xendit_invoice_id',
        'xendit_invoice_url',
        'method',
        'status',
        'reference_number',
        'paid_at',
    ];

    /**
     * Model relations.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }

    /**
     * Payment-related functions.
     */
    public static function baseQuery(array $columns = ['*'])
    {
        return DB::table('payments')->select($columns)->leftJoin('orders', 'orders.id', '=', 'payments.order_id');
    }

    public static function queryById(string $id, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)
            ->leftJoin('refunds', 'refunds.payment_id', '=', 'payments.id')
            ->where('payments.id', $id);
    }

    public static function queryByUserId(string $userId, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)
            ->selectRaw('
                EXISTS (
                    SELECT 1 
                    FROM refunds 
                    WHERE refunds.payment_id = payments.id
                ) AS refunded
            ')
            ->where('orders.user_id', $userId);
    }
}
