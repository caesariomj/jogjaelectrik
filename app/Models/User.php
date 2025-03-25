<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'city_id',
        'name',
        'email',
        'password',
        'phone_number',
        'address',
        'postal_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Model relations.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * User-related functions.
     */
    public static function baseQuery(array $columns = ['*'])
    {
        return DB::table('users')->select($columns);
    }

    public static function queryById(string $id, array $columns = ['*'])
    {
        return self::baseQuery(columns: $columns)->where('users.id', $id);
    }

    public static function queryByRole(string $role, array $columns = ['*'])
    {
        if ($role === 'user') {
            return self::baseQuery($columns)
                ->whereExists(function ($query) use ($role) {
                    $query->select(DB::raw(1))
                        ->from('model_has_roles')
                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.uuid')
                        ->whereColumn('model_has_roles.model_uuid', 'users.id')
                        ->where('model_has_roles.model_type', self::class)
                        ->where('roles.name', '=', $role);
                });
        } elseif ($role === 'admin') {
            return self::baseQuery($columns)
                ->join('model_has_roles', function ($join) {
                    $join->on('users.id', '=', 'model_has_roles.model_uuid')
                        ->where('model_has_roles.model_type', self::class);
                })
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.uuid')
                ->where('roles.name', '=', $role);
        }
    }

    public static function queryAllUsers(string $role = 'user', array $columns = ['users.id', 'users.name', 'users.email', 'users.phone_number', 'users.created_at', 'users.updated_at'])
    {
        return self::queryByRole(role: $role, columns: $columns)
            ->leftJoinSub(
                DB::table('orders')
                    ->select('user_id', DB::raw('COUNT(*) as total_orders'))
                    ->groupBy('user_id'),
                'orders',
                'orders.user_id',
                '=',
                'users.id'
            )
            ->addSelect(DB::raw('COALESCE(orders.total_orders, 0) as total_orders'));
    }

    public static function queryUserById(string $id, array $columns = ['users.id', 'users.name', 'users.email', 'users.phone_number', 'users.address', 'users.postal_code', 'users.created_at', 'users.updated_at'])
    {
        return self::queryById(id: $id, columns: $columns)
            ->leftJoin('cities', 'cities.id', '=', 'users.city_id')
            ->leftJoin('provinces', 'provinces.id', '=', 'cities.province_id')
            ->leftJoinSub(
                DB::table('orders')
                    ->select('user_id', DB::raw('COUNT(*) as total_orders'))
                    ->groupBy('user_id'),
                'orders',
                'orders.user_id',
                '=',
                'users.id'
            )
            ->addSelect('cities.name as city_name')
            ->addSelect('provinces.name as province_name')
            ->addSelect(DB::raw('COALESCE(orders.total_orders, 0) as total_orders'));
    }

    public static function queryAllAdmins(string $role = 'admin', array $columns = ['users.id', 'users.name', 'users.email', 'users.created_at', 'users.updated_at'])
    {
        return self::queryByRole(role: $role, columns: $columns)
            ->addSelect('roles.name as role');
    }

    public static function queryAdminById(string $id, string $role = 'admin', array $columns = ['users.id', 'users.name', 'users.email', 'users.created_at', 'users.updated_at'])
    {
        return self::queryById(id: $id, columns: $columns)
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_uuid')
                    ->where('model_has_roles.model_type', self::class);
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.uuid')
            ->where('roles.name', '=', $role)
            ->addSelect('roles.name as role');
    }
}
