<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DiscountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any discounts.
     */
    public function viewAny(?User $user): bool|Response
    {
        return true;
    }

    /**
     * Determine whether the user can view the discount.
     */
    public function view(User $user, Discount $discount): bool|Response
    {
        if (! $user->can('view discount details')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat detail diskon '.$discount->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can create discounts.
     */
    public function create(User $user): bool|Response
    {
        if (! $user->can('create discounts')) {
            return $this->deny('Anda tidak memiliki izin untuk membuat diskon baru.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can update the discount.
     */
    public function update(User $user, Discount $discount): bool|Response
    {
        if (! $user->can('edit discounts')) {
            return $this->deny('Anda tidak memiliki izin untuk mengubah diskon '.$discount->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the discount.
     */
    public function delete(User $user, Discount $discount): bool|Response
    {
        if (! $user->can('delete discounts')) {
            return $this->deny('Anda tidak memiliki izin untuk menghapus diskon '.$discount->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can manage the discount.
     */
    public function manage(User $user, Discount $discount): bool|Response
    {
        if (! $user->can('manage discount usage')) {
            return $this->deny('Anda tidak memiliki izin untuk mengatur penggunaan diskon '.$discount->name.'.', 403);
        }

        return true;
    }
}
