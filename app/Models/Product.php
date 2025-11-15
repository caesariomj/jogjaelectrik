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
use Illuminate\Support\Facades\DB;
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
        'cost_price',
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
    public static function baseQuery(array $columns = ['*'])
    {
        return DB::table('products')->select($columns);
    }

    public static function queryById(string $id, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('products.id', $id);
    }

    public static function queryBySlug(string $slug, array $columns = ['*'], array|string|null $relations = null)
    {
        $relations = is_array($relations) ? $relations : [$relations];

        $product = self::baseQuery(columns: $columns)->where('products.slug', $slug)->first();

        if (! $product) {
            return null;
        }

        foreach ($relations as $relation) {
            switch ($relation) {
                case 'category':
                    $categoryDetails = self::getCategoryDetails($product->subcategory_id);

                    $product->subcategory = $categoryDetails->subcategory;
                    $product->category = $categoryDetails->category;
                    break;

                case 'thumbnail':
                    $product->thumbnail = self::getImages(productId: $product->id, thumbnail: true);

                case 'images':
                    $product->images = self::getImages(productId: $product->id);
                    break;

                case 'variation':
                    $product->variation = self::getVariation(productId: $product->id)[0] ?? null;

                    if ($product->variation === null) {
                        $product->variant_id = self::getSingleVariant(productId: $product->id);
                    }

                    break;

                case 'reviews':
                    $product->reviews = self::getReviews(productId: $product->id);
                    break;

                case 'aggregates':
                    $aggregates = self::getAggregates(productId: $product->id);

                    $product->total_sold = (int) $aggregates->total_sold;
                    $product->total_stock = (int) $aggregates->total_stock;
                    $product->total_reviews = (int) $aggregates->total_reviews;
                    $product->average_rating = (float) number_format($aggregates->average_rating, 1);
                    break;

                default:
                    break;
            }
        }

        return $product;
    }

    public static function queryByName(string $slug, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('products.name', 'LIKE', '%'.$slug.'%')
            ->leftJoin('product_images', function ($join) {
                $join->on('product_images.product_id', '=', 'products.id')
                    ->where('product_images.is_thumbnail', true);
            })
            ->addSelect('product_images.file_name as thumbnail');
    }

    public static function queryAllWithRelations(array $columns = ['*'], array|string|null $relations = null)
    {
        $relations = is_array($relations) ? $relations : [$relations];

        $groupByFields = $columns;

        return self::baseQuery(columns: $columns)
            ->when(in_array('thumbnail', $relations), function ($query) use (&$groupByFields) {
                $query->leftJoin('product_images', function ($join) {
                    $join->on('product_images.product_id', '=', 'products.id')
                        ->where('product_images.is_thumbnail', true);
                });
                $query->addSelect('product_images.file_name as thumbnail');

                $groupByFields[] = 'product_images.file_name';
            })
            ->when(in_array('category', $relations), function ($query) use (&$groupByFields) {
                $query->leftJoin('subcategories', 'subcategories.id', '=', 'products.subcategory_id');
                $query->leftJoin('categories', 'categories.id', '=', 'subcategories.category_id');
                $query->addSelect([
                    'subcategories.name as subcategory_name',
                    'subcategories.slug as subcategory_slug',
                    'categories.name as category_name',
                    'categories.slug as category_slug',
                ]);

                $groupByFields = array_merge($groupByFields, [
                    'subcategories.name',
                    'subcategories.slug',
                    'categories.name',
                    'categories.slug',
                ]);
            })
            ->when(in_array('rating', $relations), function ($query) {
                $query->leftJoin('product_variants', 'product_variants.product_id', '=', 'products.id');
                $query->leftJoin('order_details', 'order_details.product_variant_id', '=', 'product_variants.id');
                $query->leftJoin('product_reviews', 'product_reviews.order_detail_id', '=', 'order_details.id');

                $query->addSelect(DB::raw('COALESCE(AVG(product_reviews.rating), 0.0) as average_rating'));
            })
            ->when(in_array('aggregates', $relations), function ($query) use (&$groupByFields) {
                $query->leftJoinSub(
                    DB::table('product_variants')
                        ->select('product_id', DB::raw('COUNT(id) as total_variants'), DB::raw('COALESCE(SUM(stock), 0) as total_stock'))
                        ->groupBy('product_id'),
                    'product_variants',
                    'product_variants.product_id',
                    '=',
                    'products.id'
                );
                $query->leftJoinSub(
                    DB::table('order_details')
                        ->join('product_variants', 'product_variants.id', '=', 'order_details.product_variant_id')
                        ->select('product_variants.product_id', DB::raw('COALESCE(SUM(order_details.quantity), 0) as total_sold'))
                        ->groupBy('product_variants.product_id'),
                    'order_details',
                    'order_details.product_id',
                    '=',
                    'products.id'
                );
                $query->addSelect([
                    'product_variants.total_variants',
                    'product_variants.total_stock',
                    'order_details.total_sold',
                ]);

                $groupByFields = array_merge($groupByFields, [
                    'product_variants.total_variants',
                    'product_variants.total_stock',
                    'order_details.total_sold',
                ]);
            })
            ->when(in_array('variations', $relations), function ($query) use (&$groupByFields) {
                $query->leftJoin('product_variants', 'products.id', '=', 'product_variants.product_id')
                    ->leftJoin('variant_combinations', 'product_variants.id', '=', 'variant_combinations.product_variant_id')
                    ->leftJoin('variation_variants', 'variant_combinations.variation_variant_id', '=', 'variation_variants.id')
                    ->leftJoin('variations', 'variation_variants.variation_id', '=', 'variations.id');

                $query->addSelect([
                    'variations.name as variation_name',
                    DB::raw("GROUP_CONCAT(DISTINCT variation_variants.name ORDER BY variation_variants.id SEPARATOR '||') as variant_names"),
                    DB::raw("GROUP_CONCAT(product_variants.id ORDER BY product_variants.id SEPARATOR '||') as variant_ids"),
                    DB::raw("GROUP_CONCAT(product_variants.variant_sku ORDER BY product_variants.id SEPARATOR '||') as variant_skus"),
                    DB::raw("GROUP_CONCAT(product_variants.price ORDER BY product_variants.id SEPARATOR '||') as variant_prices"),
                    DB::raw("GROUP_CONCAT(COALESCE(product_variants.price_discount, 0) ORDER BY product_variants.id SEPARATOR '||') as variant_price_discounts"),
                    DB::raw("GROUP_CONCAT(product_variants.stock ORDER BY product_variants.id SEPARATOR '||') as variant_stocks"),
                ]);

                $groupByFields = array_merge($groupByFields, [
                    'products.id',
                    'products.name',
                    'products.main_sku',
                    'products.is_active',
                    'variations.name',
                ]);

                $query->groupBy($groupByFields);
            })
            ->groupBy($groupByFields);
    }

    public static function getCategoryDetails(?string $subcategoryId): object
    {
        if (is_null($subcategoryId)) {
            return (object) [
                'subcategory' => null,
                'category' => null,
            ];
        }

        $result = DB::table('subcategories')
            ->select([
                'subcategories.id as subcategory_id',
                'subcategories.name as subcategory_name',
                'subcategories.slug as subcategory_slug',
                'categories.id as category_id',
                'categories.name as category_name',
                'categories.slug as category_slug',
            ])
            ->join('categories', 'categories.id', '=', 'subcategories.category_id')
            ->where('subcategories.id', $subcategoryId)
            ->first();

        return (object) [
            'subcategory' => (object) [
                'id' => $result->subcategory_id,
                'name' => $result->subcategory_name,
                'slug' => $result->subcategory_slug,
            ],
            'category' => (object) [
                'id' => $result->category_id,
                'name' => $result->category_name,
                'slug' => $result->category_slug,
            ],
        ];
    }

    public static function getImages(string $productId, bool $thumbnail = false): array
    {
        return DB::table('product_images')
            ->select([
                'id',
                'file_name',
                'is_thumbnail',
            ])
            ->where('product_id', $productId)
            ->when($thumbnail, function ($query) {
                return $query->where('is_thumbnail', true);
            })
            ->orderBy('is_thumbnail', 'desc')
            ->get()
            ->toArray();
    }

    public static function getSingleVariant(string $productId): string
    {
        $variant = DB::table('product_variants')->where('product_id', $productId)->first();

        return $variant->id;
    }

    public static function getVariation(string $productId): array
    {
        $variantSales = DB::table('order_details')
            ->select([
                'product_variant_id',
                DB::raw('SUM(quantity) as total_sold'),
            ])
            ->groupBy('product_variant_id')
            ->pluck('total_sold', 'product_variant_id');

        $variation = DB::table('variations')
            ->select([
                'variations.id',
                'variations.name',
                'variation_variants.name as variant_name',
                'product_variants.id as variant_id',
                'product_variants.variant_sku',
                'product_variants.price',
                'product_variants.price_discount',
                'product_variants.stock',
                'product_variants.is_active',
            ])
            ->join('variation_variants', 'variations.id', '=', 'variation_variants.variation_id')
            ->join('variant_combinations', 'variation_variants.id', '=', 'variant_combinations.variation_variant_id')
            ->join('product_variants', 'variant_combinations.product_variant_id', '=', 'product_variants.id')
            ->where('product_variants.product_id', $productId)
            ->get();

        return $variation
            ->groupBy('id')
            ->map(function ($variants) use ($variantSales) {
                return (object) [
                    'id' => $variants[0]->id,
                    'name' => $variants[0]->name,
                    'variants' => $variants->map(function ($variant) use ($variantSales) {
                        return (object) [
                            'id' => $variant->variant_id,
                            'name' => $variant->variant_name,
                            'sku' => $variant->variant_sku,
                            'price' => $variant->price,
                            'price_discount' => $variant->price_discount,
                            'stock' => $variant->stock,
                            'is_active' => $variant->is_active,
                            'total_sold' => $variantSales[$variant->variant_id] ?? 0,
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    public static function getReviews(string $productId): array
    {
        return DB::table('product_reviews')
            ->select([
                'product_reviews.id',
                'users.name as user_name',
                'product_reviews.rating',
                'product_reviews.review',
                'product_reviews.created_at',
            ])
            ->join('order_details', 'order_details.id', '=', 'product_reviews.order_detail_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_details.product_variant_id')
            ->join('users', 'users.id', '=', 'product_reviews.user_id')
            ->where('product_variants.product_id', $productId)
            ->limit(5)
            ->get()
            ->toArray();
    }

    public static function getAggregates(string $productId): object
    {
        $aggregates = DB::table('products')
            ->selectSub('
                SELECT COALESCE(SUM(product_variants.stock), 0)
                FROM product_variants
                WHERE product_variants.product_id = products.id
            ', 'total_stock')
            ->selectSub('
                SELECT COALESCE(SUM(order_details.quantity), 0) 
                FROM order_details 
                WHERE order_details.product_variant_id IN (
                    SELECT id FROM product_variants WHERE product_variants.product_id = products.id
                )
            ', 'total_sold')
            ->selectSub('
                SELECT COALESCE(COUNT(product_reviews.id), 0)
                FROM product_reviews
                JOIN order_details ON product_reviews.order_detail_id = order_details.id
                JOIN product_variants ON product_variants.id = order_details.product_variant_id
                WHERE product_variants.product_id = products.id
            ', 'total_reviews')
            ->selectSub('
                SELECT COALESCE(AVG(product_reviews.rating), 0.0)
                FROM product_reviews
                JOIN order_details ON product_reviews.order_detail_id = order_details.id
                JOIN product_variants ON product_variants.id = order_details.product_variant_id
                WHERE product_variants.product_id = products.id
            ', 'average_rating')
            ->where('products.id', $productId)
            ->first();

        return (object) [
            'total_stock' => (int) $aggregates->total_stock,
            'total_sold' => (int) $aggregates->total_sold,
            'total_reviews' => (int) $aggregates->total_reviews,
            'average_rating' => (float) $aggregates->average_rating,
        ];
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
