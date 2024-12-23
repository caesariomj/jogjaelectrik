<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool|Response
    {
        if (! $user->can('view all payments')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat seluruh pembayaran pesanan pelanggan.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, ?Payment $payment): bool|Response
    {
        if ($user->can('view all orders')) {
            return true;
        }

        if (! $user->can('view own payments')) {
            return $this->deny('Anda tidak memiliki izin untuk melihat seluruh pembayaran pesanan Anda.', 403);
        }

        if ($payment->id === null) {
            return true;
        }

        if ($user->id !== $payment->order->user_id) {
            return $this->deny('Anda hanya dapat melihat pembayaran pesanan Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can pay the orders.
     */
    public function pay(User $user, Payment $payment): bool|Response
    {
        if (! $user->can('pay orders')) {
            return $this->deny('Anda tidak memiliki izin untuk membayar pesanan dengan nomor: '.$payment->order->order_number.'.', 403);
        }

        if ($user->id !== $payment->order->user_id) {
            return $this->deny('Anda hanya dapat membayar pesanan Anda sendiri.', 403);
        }

        return true;
    }

    /**
     * Determine whether the user can refund the payments.
     */
    public function refund(User $user, Payment $payment): bool|Response
    {
        if (! $user->can('refund payments')) {
            return $this->deny('Anda tidak memiliki izin untuk me-refund pembayaran pesanan dengan nomor: '.$payment->order->order_number.'.', 403);
        }

        return true;
    }
}
