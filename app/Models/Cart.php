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
    public static function baseQuery(array $columns = ['*'])
    {
        return DB::table('carts')->select($columns);
    }

    public static function queryByUserIdWithRelations(string $userId, array $columns = ['*'], array|string|null $relations = null)
    {
        $relations = is_array($relations) ? $relations : [$relations];

        return self::baseQuery($columns)->where('carts.user_id', $userId)
            ->when(in_array('items', $relations), function ($query) {
                $query->leftJoin('cart_items', 'cart_items.cart_id', '=', 'carts.id');
                $query->leftJoin('product_variants', 'product_variants.id', '=', 'cart_items.product_variant_id');
                $query->leftJoin('variant_combinations', function ($join) {
                    $join
                        ->on('variant_combinations.product_variant_id', '=', 'product_variants.id')
                        ->whereNotNull('product_variants.variant_sku');
                });
                $query->leftJoin('variation_variants', function ($join) {
                    $join
                        ->on('variation_variants.id', '=', 'variant_combinations.variation_variant_id')
                        ->whereNotNull('product_variants.variant_sku');
                });
                $query->leftJoin('variations', function ($join) {
                    $join
                        ->on('variations.id', '=', 'variation_variants.variation_id')
                        ->whereNotNull('product_variants.variant_sku');
                });
                $query->leftJoin('products', 'products.id', '=', 'product_variants.product_id');
                $query->leftJoinSub(
                    DB::table('subcategories')->select('id', 'slug', 'category_id'),
                    'subcategories',
                    'subcategories.id',
                    '=',
                    'products.subcategory_id',
                );
                $query->leftJoinSub(
                    DB::table('categories')->select('id', 'slug'),
                    'categories',
                    'categories.id',
                    '=',
                    'subcategories.category_id',
                );
                $query->leftJoinSub(
                    DB::table('product_images')
                        ->select('product_id', 'file_name')
                        ->where('is_thumbnail', true),
                    'product_images',
                    'product_images.product_id',
                    '=',
                    'products.id',
                );
            })
            ->when(in_array('discount', $relations), function ($query) {
                $query->leftJoin('discounts', 'discounts.id', '=', 'carts.discount_id');
            })
            ->when(in_array('user', $relations), function ($query) {
                $query->leftJoin('users', 'users.id', '=', 'carts.user_id');
                $query->leftJoin('cities', 'cities.id', '=', 'users.city_id');
                $query->leftJoin('provinces', 'provinces.id', '=', 'cities.province_id');
            });
    }
}
