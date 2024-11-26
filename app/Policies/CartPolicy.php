<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CartPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Cart $cart): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu untuk melihat keranjang belanja.', 401);
        }

        if (! $user->can('view own cart')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat keranjang belanja ini.', 403);
        }

        if ($user->id !== $cart->user_id) {
            return $this->deny('Anda hanya dapat melihat keranjang belanja Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu sebelum menambahkan produk ke dalam keranjang belanja.', 401);
        }

        if (! $user->can('create cart')) {
            return $this->deny('Anda tidak memiliki izin untuk membuat keranjang belanja.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, Cart $cart): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu sebelum mengubah produk di dalam keranjang belanja anda.', 401);
        }

        if (! $user->can('edit cart')) {
            return $this->deny('Anda tidak memiliki izin untuk mengubah keranjang belanja ini.', 403);
        }

        if ($user->id !== $cart->user_id) {
            return $this->deny('Anda hanya dapat mengubah keranjang belanja Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, Cart $cart): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu untuk melihat keranjang belanja.', 401);
        }

        if (! $user->can('delete cart')) {
            return $this->deny('Anda tidak memiliki izin untuk menghapus keranjang belanja ini.', 403);
        }

        if ($user->id !== $cart->user_id) {
            return $this->deny('Anda hanya dapat menghapus keranjang belanja Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can use the discount on the model.
     */
    public function applyDiscount(?User $user, Cart $cart): bool|Response
    {
        if (! $user) {
            return $this->deny('Silakan masuk terlebih dahulu untuk menerapkan diskon.', 401);
        }

        if (! $user->can('apply discounts')) {
            return $this->deny('Anda tidak memiliki izin untuk menerapkan diskon pada keranjang belanja ini.', 403);
        }

        if ($user->id !== $cart->user_id) {
            return $this->deny('Anda hanya dapat menerapkan diskon pada keranjang belanja Anda sendiri.', 403);
        }

        return true;
    }
}
