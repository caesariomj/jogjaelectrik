<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Subcategory extends Model
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
        'category_id',
        'name',
        'slug',
    ];

    /**
     * Model relations.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Subcategory-related functions.
     */
    public static function baseQuery(array $columns = ['*'])
    {
        return DB::table('subcategories')->select($columns);
    }

    public static function queryById(string $id, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('subcategories.id', $id);
    }

    public static function queryBySlug(string $slug, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('subcategories.slug', $slug);
    }

    public static function queryByName(string $name, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('subcategories.name', $name);
    }

    public static function queryBySlugWithCategory(string $slug, array $columns = ['subcategories.id', 'subcategories.name', 'subcategories.slug', 'subcategories.created_at', 'subcategories.updated_at'])
    {
        return self::queryBySlug(slug: $slug, columns: $columns)
            ->leftJoin('categories', 'categories.id', '=', 'subcategories.category_id')
            ->addSelect('categories.id as category_id')
            ->addSelect('categories.name as category_name');
    }

    public static function queryAllWithCategoryAndTotalProduct(array $columns = ['subcategories.id', 'subcategories.name', 'subcategories.slug', 'subcategories.created_at', 'subcategories.updated_at'])
    {
        return self::baseQuery(columns: $columns)
            ->leftJoin('categories', 'categories.id', '=', 'subcategories.category_id')
            ->leftJoinSub(
                DB::table('products')
                    ->select('subcategory_id', DB::raw('COUNT(*) as total_products'))
                    ->groupBy('subcategory_id'),
                'products',
                'products.subcategory_id',
                '=',
                'subcategories.id'
            )
            ->addSelect('categories.name as category_name')
            ->addSelect(DB::raw('COALESCE(products.total_products, 0) as total_products'));
    }

    public static function queryBySlugWithCategoryAndTotalProduct(string $slug, array $columns = ['subcategories.id', 'subcategories.name', 'subcategories.slug', 'subcategories.created_at', 'subcategories.updated_at'])
    {
        return self::queryBySlug(slug: $slug, columns: $columns)
            ->leftJoin('categories', 'categories.id', '=', 'subcategories.category_id')
            ->leftJoinSub(
                DB::table('products')
                    ->select('subcategory_id', DB::raw('COUNT(*) as total_products'))
                    ->groupBy('subcategory_id'),
                'products',
                'products.subcategory_id',
                '=',
                'subcategories.id'
            )
            ->addSelect('categories.name as category_name')
            ->addSelect(DB::raw('COALESCE(products.total_products, 0) as total_products'));
    }

    /**
     * The "booted" method of the model.
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($subcategory) {
            $subcategory->slug = Str::slug($subcategory->name);
        });

        static::updating(function ($subcategory) {
            $subcategory->slug = Str::slug($subcategory->name);
        });
    }
}
