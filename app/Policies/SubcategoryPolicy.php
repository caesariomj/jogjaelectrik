<?php

namespace App\Policies;

use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class SubcategoryPolicy
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
    public function view(User $user, Subcategory $subcategory): bool|Response
    {
        if (! $user->can('view subcategory details')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat detail subkategori '.$subcategory->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool|Response
    {
        if (! $user->can('create subcategories')) {
            return $this->deny('Anda tidak memiliki izin untuk membuat subkategori baru.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Subcategory $subcategory): bool|Response
    {
        if (! $user->can('edit subcategories')) {
            return $this->deny('Anda tidak memiliki izin untuk mengubah subkategori '.$subcategory->name.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Subcategory $subcategory): bool|Response
    {
        if (! $user->can('delete subcategories')) {
            return $this->deny('Anda tidak memiliki izin untuk menghapus subkategori '.$subcategory->name.'.', 403);
        }

        return true;
    }
}
