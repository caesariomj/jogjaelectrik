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
    public function viewAny(?User $user): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu untuk melihat seluruh pesanan pelanggan.', 401);
        }

        if (! $user->can('view all orders')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat seluruh pesanan pelanggan.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(?User $user, Order $order): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu untuk melihat seluruh pesanan Anda.', 401);
        }

        if ($user->can('view all orders')) {
            return true;
        }

        if (! $user->can('view own orders')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat seluruh pesanan Anda.', 403);
        }

        if ($user->id !== $order->user_id) {
            return $this->deny('Anda hanya dapat melihat pesanan Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(?User $user): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu untuk melakukan checkout pesanan.', 401);
        }

        if (! $user->can('create orders')) {
            return $this->deny('Anda tidak memiliki izin untuk melakukan checkout pesanan.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(?User $user, Order $order): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu untuk membatalkan pesanan ini.', 401);
        }

        if (! $user->can('cancel all orders')) {
            if (! $user->can('cancel own orders')) {
                return $this->deny('Anda tidak memiliki izin untuk membatalkan pesanan ini.', 403);
            }

            if ($user->id !== $order->user_id) {
                return $this->deny('Anda hanya dapat membatalkan pesanan Anda sendiri.', 403);
            }
        }

        return true;
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(?User $user, Order $order): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu untuk memproses pesanan pengguna.', 401);
        }

        if (! $user->can('update orders')) {
            return $this->deny('Anda tidak memiliki izin untuk memproses pesanan pengguna.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(?User $user, Order $order): bool
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu untuk menghapus pesanan pengguna.', 401);
        }

        if (! $user->can('delete orders')) {
            return $this->deny('Anda tidak memiliki izin untuk menghapus pesanan pengguna.', 403);
        }

        return true;
    }
}
