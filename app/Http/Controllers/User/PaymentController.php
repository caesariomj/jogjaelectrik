<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        return view('pages.user.payments.index');
    }

    public function show(string $id): View|RedirectResponse
    {
        $payment = Payment::queryById(
            id: $id,
            columns: [
                'payments.id',
                'payments.method',
                'payments.status',
                'payments.reference_number',
                'orders.user_id',
                'orders.order_number',
                'orders.subtotal_amount',
                'orders.discount_amount',
                'orders.shipping_cost_amount',
                'orders.total_amount',
                'payments.paid_at',
                'payments.created_at',
                'payments.updated_at',
                'refunds.status as refund_status',
                'refunds.rejection_reason',
                'refunds.created_at as refund_created_at',
                'refunds.approved_at',
                'refunds.succeeded_at',
            ]
        )->first();

        if (! $payment) {
            session()->flash('error', 'Riwayat transaksi dengan ID '.$id.' tidak ditemukan.');

            return redirect()->route('transactions.index');
        }

        $data = (object) [
            'id' => $payment->id,
            'method' => $payment->method,
            'status' => $payment->status,
            'reference_number' => $payment->reference_number,
            'order' => (object) [
                'user_id' => $payment->user_id,
                'order_number' => $payment->order_number,
                'subtotal_amount' => $payment->subtotal_amount,
                'discount_amount' => $payment->discount_amount < 0 ? str_replace('-', '', $payment->discount_amount) : null,
                'shipping_cost_amount' => $payment->shipping_cost_amount,
                'total_amount' => $payment->total_amount,
            ],
            'refund' => (object) [
                'status' => $payment->refund_status,
                'rejection_reason' => $payment->rejection_reason,
                'created_at' => $payment->refund_created_at,
                'approved_at' => $payment->approved_at,
                'succeeded_at' => $payment->succeeded_at,
            ],
            'paid_at' => $payment->paid_at,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ];

        $data->refund = is_null($data->refund->status) ? null : $data->refund;

        $payment = (new Payment)->newFromBuilder($data);

        try {
            $this->authorize('view', $payment);

            return view('pages.user.payments.show', compact('payment'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('transactions.index');
        }
    }
}
