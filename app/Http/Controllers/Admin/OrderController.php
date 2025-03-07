<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $this->authorize('viewAny', Order::class);

            return view('pages.admin.orders.index');
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    public function show(string $orderNumber): View|RedirectResponse
    {
        // $order = Order::with(['details.productVariant.product.images', 'payment', 'user.city.province'])->where('order_number', $orderNumber)->first();

        $order = Order::queryByOrderNumber(orderNumber: $orderNumber, columns: [
            'orders.id',
            'orders.order_number',
            'orders.status',
            'orders.shipping_address',
            'orders.shipping_courier',
            'orders.estimated_shipping_min_days',
            'orders.estimated_shipping_max_days',
            'orders.shipment_tracking_number',
            'orders.note',
            'orders.subtotal_amount',
            'orders.discount_amount',
            'orders.shipping_cost_amount',
            'orders.total_amount',
            'orders.cancelation_reason',
            'orders.created_at',
        ], relations: [
            'order_details',
            'user',
            'payment',
        ])
            ->get();

        // pembayaran, refund, pengiriman, pelanggan, detail

        // dd($order);

        if (! $order) {
            session()->flash('error', 'Pesanan dengan nomor '.$orderNumber.' tidak ditemukan.');

            return redirect()->route('admin.orders.index');
        }

        $order = $order
            ->groupBy('id')
            ->map(function ($order) {
                $firstOrder = $order->first();

                $details = $order->map(function ($detail) {
                    return (object) [
                        'id' => $detail->order_detail_id,
                        'name' => $detail->product_name,
                        'sku' => $detail->product_variant_sku
                            ? $detail->product_main_sku.'-'.$detail->product_variant_sku
                            : $detail->product_main_sku,
                        'slug' => $detail->product_slug,
                        'variant' => $detail->variant_name,
                        'variation' => $detail->variation_name,
                        'price' => $detail->order_detail_price,
                        'quantity' => $detail->order_detail_quantity,
                        'thumbnail' => $detail->thumbnail,
                    ];
                });

                $user = (object) [
                    'name' => $firstOrder->user_name,
                    'email' => $firstOrder->user_email,
                    'phone_number' => '+62-'.Crypt::decryptString($firstOrder->user_phone_number),
                    'postal_code' => Crypt::decryptString($firstOrder->user_postal_code),
                    'city' => $firstOrder->city,
                    'province' => $firstOrder->province,
                ];

                $payment = (object) [
                    'xendit_invoice_id' => $firstOrder->payment_xendit_invoice_id,
                    'xendit_invoice_url' => $firstOrder->payment_xendit_invoice_url,
                    'method' => $firstOrder->payment_method,
                    'status' => $firstOrder->payment_status,
                    'reference_number' => $firstOrder->payment_reference_number,
                    'paid_at' => $firstOrder->payment_paid_at,
                ];

                $refund = (object) [
                    'xendit_refund_id' => $firstOrder->refund_xendit_refund_id,
                    'status' => $firstOrder->refund_status,
                    'rejection_reason' => $firstOrder->refund_rejection_reason,
                    'approved_at' => $firstOrder->refund_approved_at,
                    'succeeded_at' => $firstOrder->refund_succeeded_at,
                    'created_at' => $firstOrder->refund_created_at,
                ];

                return (object) [
                    'id' => $firstOrder->id,
                    'order_number' => $firstOrder->order_number,
                    'status' => $firstOrder->status,
                    'shipping_address' => Crypt::decryptString($firstOrder->shipping_address),
                    'shipping_courier' => $firstOrder->shipping_courier,
                    'estimated_shipping_min_days' => $firstOrder->estimated_shipping_min_days,
                    'estimated_shipping_max_days' => $firstOrder->estimated_shipping_max_days,
                    'shipment_tracking_number' => $firstOrder->shipment_tracking_number,
                    'note' => $firstOrder->note,
                    'subtotal_amount' => $firstOrder->subtotal_amount,
                    'discount_amount' => $firstOrder->discount_amount ? str_replace('-', '', $firstOrder->discount_amount) : null,
                    'shipping_cost_amount' => $firstOrder->shipping_cost_amount,
                    'total_amount' => $firstOrder->total_amount,
                    'cancelation_reason' => $firstOrder->cancelation_reason,
                    'created_at' => $firstOrder->created_at,
                    'details' => $details,
                    'user' => $user,
                    'payment' => $payment,
                    'refund' => $refund,
                ];
            })
            ->values();

        $order = (new Order)->newFromBuilder($order->first());

        try {
            $this->authorize('view', $order);

            return view('pages.admin.orders.show', compact('order'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.orders.index');
        }
    }
}
