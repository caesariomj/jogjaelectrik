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

    public static function queryBySlug(string $slug, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('categories.slug', $slug);
    }

    public static function queryByName(string $name, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('categories.name', $name);
    }

    public static function queryAllWithTotalProduct(array $columns = ['categories.id', 'categories.name', 'categories.slug', 'categories.is_primary', 'categories.created_at', 'categories.updated_at'])
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

    public static function queryBySlugWithTotalSubcategoryAndProduct(string $slug, array $columns = ['categories.id', 'categories.name', 'categories.slug', 'categories.is_primary', 'categories.created_at', 'categories.updated_at'])
    {
        return self::queryBySlug(slug: $slug, columns: $columns)
            ->leftJoinSub(
                DB::table('subcategories')
                    ->select('category_id', DB::raw('COUNT(*) as total_subcategories'))
                    ->groupBy('category_id'),
                'subcategories',
                'subcategories.category_id',
                '=',
                'categories.id'
            )
            ->leftJoinSub(
                DB::table('products')
                    ->join('subcategories', 'subcategories.id', '=', 'products.subcategory_id')
                    ->select('subcategories.category_id', DB::raw('COUNT(*) as total_products'))
                    ->groupBy('subcategories.category_id'),
                'products',
                'products.category_id',
                '=',
                'categories.id'
            )
            ->addSelect(DB::raw('COALESCE(subcategories.total_subcategories, 0) as total_subcategories'))
            ->addSelect(DB::raw('COALESCE(products.total_products, 0) as total_products'));
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
