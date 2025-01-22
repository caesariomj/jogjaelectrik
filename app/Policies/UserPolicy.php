<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool|Response
    {
        if (! $user->can('view all accounts')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat seluruh akun pelanggan.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $model): bool|Response
    {
        if ($user->can('view account details')) {
            return true;
        }

        if (! $user->can('view own account')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat profil akun Anda.', 403);
        }

        if ($user->id !== $model->id) {
            return $this->deny('Anda hanya dapat melihat profil akun Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool|Response
    {
        if (! $user->can('create accounts')) {
            return $this->deny('Anda tidak memiliki izin untuk membuat akun baru.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool|Response
    {
        if ($user->can('edit all accounts')) {
            return true;
        }

        if (! $user->can('edit own account')) {
            return $this->deny('Anda tidak memiliki izin untuk mengubah profil akun Anda.', 403);
        }

        if ($user->id !== $model->id) {
            return $this->deny('Anda hanya dapat mengubah profil akun Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool|Response
    {
        if ($user->can('delete all accounts')) {
            return true;
        }

        if (! $user->can('delete own account')) {
            return $this->deny('Anda tidak memiliki izin untuk menghapus akun Anda.', 403);
        }

        if ($user->id !== $model->id) {
            return $this->deny('Anda hanya dapat menghapus akun Anda sendiri.', 403);
        }

        return true;
    }
}
