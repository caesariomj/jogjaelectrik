<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class RefundPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool|Response
    {
        if (! $user->can('view refunds')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat seluruh permintaan refund pelanggan.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Refund $refund): bool|Response
    {
        if ($user->can('view refunds')) {
            return true;
        }

        if (! $user->can('view refund details')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat detail refund pesanan dengan nomor: '.$refund->payment->order->order_number.'.', 403);
        }

        if ($user->id !== $refund->payment->order->user_id) {
            return $this->deny('Anda hanya dapat melihat detail refund pesanan Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool|Response
    {
        if (! $user->can('create refunds')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat seluruh permintaan refund pelanggan.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function process(User $user, Refund $refund): bool|Response
    {
        if (! $user->can('process refunds')) {
            return $this->deny('Anda tidak memiliki izin untuk memproses permintaan refund pesanan dengan nomor: '.$refund->payment->order->order_number.'.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function reject(User $user, Refund $refund): bool|Response
    {
        if (! $user->can('reject refunds')) {
            return $this->deny('Anda tidak memiliki izin untuk menolak permintaan refund pesanan dengan nomor: '.$refund->payment->order->order_number.'.', 403);
        }

        return true;
    }
}
