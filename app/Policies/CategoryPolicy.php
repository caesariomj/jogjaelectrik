<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, Category $category): bool|Response
    {
        if (! $user->can('view category details')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat detail kategori '.$category->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can create categorys.
     */
    public function create(User $user): bool|Response
    {
        if (! $user->can('create categories')) {
            return $this->deny('Anda tidak memiliki izin untuk membuat kategori baru.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Category $category): bool|Response
    {
        if (! $user->can('edit categories')) {
            return $this->deny('Anda tidak memiliki izin untuk mengubah kategori '.$category->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Category $category): bool|Response
    {
        if (! $user->can('delete categories')) {
            return $this->deny('Anda tidak memiliki izin untuk menghapus kategori '.$category->name.'.', 403);
        }

        return true;
    }
}
