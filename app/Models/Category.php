<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Category extends Model
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
        'slug',
        'is_primary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Model relations.
     */
    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, Subcategory::class);
    }

    /**
     * Category-related functions.
     */
    public static function baseQuery(array $columns = ['*'])
    {
        return DB::table('categories')->select($columns);
    }

    public static function queryPrimary(array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('categories.is_primary', true);
    }

    public static function queryById(string $id, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('categories.id', $id);
    }

    public static function queryBySlug(string $slug, array $columns = ['*'], array|string|null $relations = null)
    {
        $relations = is_array($relations) ? $relations : [$relations];

        $category = self::baseQuery(columns: $columns)->where('categories.slug', $slug)->first();

        if (! $category) {
            return null;
        }

        foreach ($relations as $relation) {
            switch ($relation) {
                case 'subcategories':
                    $category->subcategories = self::getSubcategories(categoryId: $category->id);
                    break;

                case 'aggregates':
                    $aggregates = self::getAggregates(categoryId: $category->id);

                    $category->total_subcategories = $aggregates->total_subcategories;
                    $category->total_products = $aggregates->total_products;
                    break;

                default:
                    break;
            }
        }

        return $category;
    }

    public static function queryByName(string $name, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('categories.name', $name);
    }

    public static function queryAllWithTotalProduct(array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)
            ->leftJoinSub(
                DB::table('products')
                    ->join('subcategories', 'subcategories.id', '=', 'products.subcategory_id')
                    ->selectRaw('subcategories.category_id, COUNT(*) as total_products')
                    ->groupBy('subcategories.category_id'),
                'products',
                'products.category_id',
                '=',
                'categories.id',
            )
            ->addSelect(DB::raw('COALESCE(products.total_products, 0) as total_products'));
    }

    public static function queryPrimaryWithSubcategories(array $columns = ['*'])
    {
        return self::queryPrimary($columns)
            ->leftJoin('subcategories', 'categories.id', '=', 'subcategories.category_id')
            ->addSelect([
                'subcategories.id as subcategory_id',
                'subcategories.name as subcategory_name',
                'subcategories.slug as subcategory_slug',
            ]);
    }

    public static function getSubcategories(string $categoryId): array
    {
        return DB::table('subcategories')
            ->select('name')
            ->where('subcategories.category_id', $categoryId)
            ->get()
            ->toArray();
    }

    public static function getAggregates(string $categoryId): object
    {
        $aggregates = DB::table('categories')
            ->selectSub('
                SELECT COUNT(subcategories.id)
                FROM subcategories
                WHERE subcategories.category_id = categories.id
            ', 'total_subcategories')
            ->selectSub('
                SELECT COUNT(products.id)
                FROM products
                LEFT JOIN subcategories ON subcategories.id = products.subcategory_id
                WHERE subcategories.category_id = categories.id OR products.subcategory_id IS NULL
            ', 'total_products')
            ->where('categories.id', $categoryId)
            ->first();

        return (object) [
            'total_subcategories' => $aggregates->total_subcategories,
            'total_products' => $aggregates->total_products,
        ];
    }

    /**
     * The "booted" method of the model.
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });

        static::updating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }
}
