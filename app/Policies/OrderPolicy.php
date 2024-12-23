<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool|Response
    {
        if (! $user->can('view all orders')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat seluruh pesanan pelanggan.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, ?Order $order): bool|Response
    {
        if ($user->can('view all orders')) {
            return true;
        }

        if (! $user->can('view own orders')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat seluruh pesanan Anda.', 403);
        }

        if ($order->id === null) {
            return true;
        }

        if ($user->id !== $order->user_id) {
            return $this->deny('Anda hanya dapat melihat pesanan Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool|Response
    {
        if (! $user->can('create orders')) {
            return $this->deny('Anda tidak memiliki izin untuk melakukan checkout pesanan.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool|Response
    {
        if ($user->can('cancel all orders')) {
            return true;
        }

        if (! $user->can('cancel own orders')) {
            return $this->deny('Anda tidak memiliki izin untuk membatalkan pesanan dengan nomor: '.$order->order_number.'.', 403);
        }

        if ($user->id !== $order->user_id) {
            return $this->deny('Anda hanya dapat membatalkan pesanan Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool|Response
    {
        if ($user->can('update all orders')) {
            return true;
        }

        if (! $user->can('update own orders')) {
            return $this->deny('Anda tidak memiliki izin untuk memproses pesanan dengan nomor: '.$order->order_number.'.', 403);
        }

        if ($user->id !== $order->user_id) {
            return $this->deny('Anda hanya dapat memproses pesanan Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        if (! $user->can('delete orders')) {
            return $this->deny('Anda tidak memiliki izin untuk menghapus pesanan dengan nomor: '.$order->order_number.'.', 403);
        }

        return true;
    }
}
