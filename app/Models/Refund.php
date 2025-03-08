<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Refund extends Model
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
        'payment_id',
        'xendit_refund_id',
        'status',
        'rejection_reason',
        'approved_at',
        'succeeded_at',
    ];

    /**
     * Model relations.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Refund-related functions.
     */
    public static function baseQuery(array $columns = ['*'])
    {
        return DB::table('refunds')->select($columns);
    }

    public static function queryById(string $id, array $columns = ['*'])
    {
        $refund = self::baseQuery(columns: $columns)->where('refunds.id', $id)->first();

        $refund->payment = self::getPayment(paymentId: $refund->payment_id);

        $refund->order = self::getOrder(orderId: $refund->payment->order_id);

        return $refund;
    }

    public static function queryAllWithRelations(array $columns = ['*'], array|string|null $relations = null)
    {
        $relations = is_array($relations) ? $relations : [$relations];

        return self::baseQuery(columns: $columns)
            ->when(in_array('payment', $relations), function ($query) {
                $query->leftJoin('payments', 'payments.id', '=', 'refunds.payment_id');
                $query->leftJoin('orders', 'orders.id', '=', 'payments.order_id');
                $query->addSelect([
                    'payments.method as payment_method',
                    'orders.order_number',
                    'orders.total_amount',
                ]);
            });
    }

    private static function getPayment(string $paymentId)
    {
        return DB::table('payments')->where('id', $paymentId)->select('order_id', 'method')->first();
    }

    private static function getOrder(string $orderId)
    {
        return DB::table('orders')->where('id', $orderId)->select('order_number', 'total_amount')->first();
    }
}
