<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProductReviewPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create reviews.
     */
    public function create(User $user): bool|Response
    {
        if (! $user->can('create reviews')) {
            return $this->deny('Anda tidak memiliki izin untuk membuat penilaian produk.', 403);
        }

        return true;
    }
}
