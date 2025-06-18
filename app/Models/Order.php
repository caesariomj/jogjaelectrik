<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
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
        'discount_id',
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

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Order-related functions.
     */
    public static function baseQuery(array $columns = ['*'])
    {
        return DB::table('orders')->select($columns);
    }

    public static function queryByUserId(string $userId, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('orders.user_id', $userId);
    }

    public static function queryByOrderNumber(string $orderNumber, array $columns = ['*'], array|string|null $relations = null)
    {
        $relations = is_array($relations) ? $relations : [$relations];

        return self::baseQuery(columns: $columns)->where('orders.order_number', $orderNumber)
            ->when(in_array('order_details', $relations), function ($query) {
                $query->leftJoin('order_details', 'order_details.order_id', '=', 'orders.id');
                $query->leftJoin('product_variants', 'product_variants.id', '=', 'order_details.product_variant_id');
                $query->leftJoin('variant_combinations', 'variant_combinations.product_variant_id', '=', 'product_variants.id');
                $query->leftJoin('variation_variants', 'variation_variants.id', '=', 'variant_combinations.variation_variant_id');
                $query->leftJoin('variations', 'variations.id', '=', 'variation_variants.variation_id');
                $query->leftJoin('products', 'products.id', '=', 'product_variants.product_id');
                $query->leftJoin('product_images', function ($join) {
                    $join->on('product_images.product_id', '=', 'products.id')
                        ->where('product_images.is_thumbnail', true);
                });
                $query->leftJoin('subcategories', 'subcategories.id', '=', 'products.subcategory_id');
                $query->leftJoin('categories', 'categories.id', '=', 'subcategories.category_id');
                $query->addSelect([
                    'order_details.id as order_detail_id',
                    'order_details.price as order_detail_price',
                    'order_details.quantity as order_detail_quantity',
                    'product_variants.variant_sku as product_variant_sku',
                    'products.name as product_name',
                    'products.slug as product_slug',
                    'products.main_sku as product_main_sku',
                    'variation_variants.name as variant_name',
                    'variations.name as variation_name',
                    'product_images.file_name as thumbnail',
                    'subcategories.slug as subcategory_slug',
                    'categories.slug as category_slug',
                ]);
            })
            ->when(in_array('user', $relations), function ($query) {
                $query->leftJoin('users', 'users.id', '=', 'orders.user_id');
                $query->addSelect([
                    'users.name as user_name',
                    'users.email as user_email',
                    'users.phone_number as user_phone_number',
                ]);
            })
            ->when(in_array('payment', $relations), function ($query) {
                $query->leftJoin('payments', 'payments.order_id', '=', 'orders.id');
                $query->leftJoin('refunds', 'refunds.payment_id', '=', 'payments.id');
                $query->addSelect([
                    'payments.xendit_invoice_id as payment_xendit_invoice_id',
                    'payments.xendit_invoice_url as payment_xendit_invoice_url',
                    'payments.method as payment_method',
                    'payments.status as payment_status',
                    'payments.reference_number as payment_reference_number',
                    'payments.paid_at as payment_paid_at',
                    'refunds.xendit_refund_id as refund_xendit_refund_id',
                    'refunds.status as refund_status',
                    'refunds.rejection_reason as refund_rejection_reason',
                    'refunds.approved_at as refund_approved_at',
                    'refunds.succeeded_at as refund_succeeded_at',
                    'refunds.created_at as refund_created_at',
                ]);
            });
    }

    public static function queryAllByStatusWithRelations(string $status, array $columns = ['*'], array|string|null $relations = null)
    {
        $relations = is_array($relations) ? $relations : [$relations];

        return self::baseQuery(columns: $columns)
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('orders.status', $status);
            })
            ->when(in_array('order_details', $relations), function ($query) {
                $query->leftJoin('order_details', 'order_details.order_id', '=', 'orders.id');
                $query->leftJoin('product_variants', 'product_variants.id', '=', 'order_details.product_variant_id');
                $query->leftJoin('variant_combinations', 'variant_combinations.product_variant_id', '=', 'product_variants.id');
                $query->leftJoin('variation_variants', 'variation_variants.id', '=', 'variant_combinations.variation_variant_id');
                $query->leftJoin('variations', 'variations.id', '=', 'variation_variants.variation_id');
                $query->leftJoin('products', 'products.id', '=', 'product_variants.product_id');
                $query->leftJoin('product_images', function ($join) {
                    $join->on('product_images.product_id', '=', 'products.id')
                        ->where('product_images.is_thumbnail', true);
                });
                $query->leftJoin('subcategories', 'subcategories.id', '=', 'products.subcategory_id');
                $query->leftJoin('categories', 'categories.id', '=', 'subcategories.category_id');
                $query->addSelect([
                    'order_details.id as order_detail_id',
                    'order_details.price as order_detail_price',
                    'order_details.quantity as order_detail_quantity',
                    'product_variants.id as product_variant_id',
                    'product_variants.variant_sku as product_variant_sku',
                    'products.name as product_name',
                    'products.slug as product_slug',
                    'products.main_sku as product_main_sku',
                    'variation_variants.name as variant_name',
                    'variations.name as variation_name',
                    'product_images.file_name as thumbnail',
                    'subcategories.name as subcategory_name',
                    'categories.name as category_name',
                ]);
            })
            ->when(in_array('user', $relations), function ($query) {
                $query->leftJoin('users', 'users.id', '=', 'orders.user_id');
                $query->leftJoin('cities', 'cities.id', '=', 'users.city_id');
                $query->leftJoin('provinces', 'provinces.id', '=', 'cities.province_id');
                $query->addSelect([
                    'users.name as user_name',
                    'users.phone_number as user_phone_number',
                    'users.postal_code as user_postal_code',
                    'cities.name as city',
                    'provinces.name as province',
                ]);
            })
            ->when(in_array('payment', $relations), function ($query) {
                $query->leftJoin('payments', 'payments.order_id', '=', 'orders.id');
                $query->addSelect([
                    'payments.method as payment_method',
                ]);
            });
    }

    public static function queryAllByUserIdAndStatusWithRelations(string $userId, string $status, array $columns = ['*'], array|string|null $relations = null)
    {
        $relations = is_array($relations) ? $relations : [$relations];

        return self::queryByUserId(userId: $userId, columns: $columns)
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('orders.status', $status);
            })
            ->when(in_array('order_details', $relations), function ($query) {
                $query->leftJoin('order_details', 'order_details.order_id', '=', 'orders.id');
                $query->leftJoin('product_variants', 'product_variants.id', '=', 'order_details.product_variant_id');
                $query->leftJoin('variant_combinations', 'variant_combinations.product_variant_id', '=', 'product_variants.id');
                $query->leftJoin('variation_variants', 'variation_variants.id', '=', 'variant_combinations.variation_variant_id');
                $query->leftJoin('variations', 'variations.id', '=', 'variation_variants.variation_id');
                $query->leftJoin('products', 'products.id', '=', 'product_variants.product_id');
                $query->leftJoin('product_images', function ($join) {
                    $join->on('product_images.product_id', '=', 'products.id')
                        ->where('product_images.is_thumbnail', true);
                });
                $query->leftJoin('subcategories', 'subcategories.id', '=', 'products.subcategory_id');
                $query->leftJoin('categories', 'categories.id', '=', 'subcategories.category_id');
                $query->addSelect([
                    'order_details.id as order_detail_id',
                    'order_details.price as order_detail_price',
                    'order_details.quantity as order_detail_quantity',
                    'product_variants.id as product_variant_id',
                    'product_variants.variant_sku as product_variant_sku',
                    'products.name as product_name',
                    'products.slug as product_slug',
                    'products.main_sku as product_main_sku',
                    'variation_variants.name as variant_name',
                    'variations.name as variation_name',
                    'product_images.file_name as thumbnail',
                    'subcategories.slug as subcategory_slug',
                    'categories.slug as category_slug',
                ]);
            })
            ->when(in_array('user', $relations), function ($query) {
                $query->leftJoin('users', 'users.id', '=', 'orders.user_id');
                $query->addSelect([
                    'users.name as user_name',
                    'users.phone_number as user_phone_number',
                ]);
            })
            ->when(in_array('payment', $relations), function ($query) {
                $query->leftJoin('payments', 'payments.order_id', '=', 'orders.id');
                $query->addSelect([
                    'payments.method as payment_method',
                ]);
            });
    }

    public function hasBeenReviewed()
    {
        return $this->details()->whereHas('productReview')->exists();
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
