<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool|Response
    {
        if (! $user->can('create products')) {
            return $this->deny('Anda tidak memiliki izin untuk membuat produk baru.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool|Response
    {
        if (! $user->can('edit products')) {
            return $this->deny('Anda tidak memiliki izin untuk mengubah produk '.$product->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool|Response
    {
        if (! $user->can('archive products')) {
            return $this->deny('Anda tidak memiliki izin untuk mengarsip produk '.$product->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool|Response
    {
        if (! $user->can('restore products')) {
            return $this->deny('Anda tidak memiliki izin untuk memulihkan produk '.$product->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool|Response
    {
        if (! $user->can('force delete products')) {
            return $this->deny('Anda tidak memiliki izin untuk menghapus produk '.$product->name.'.', 403);
        }

        return true;
    }
}
