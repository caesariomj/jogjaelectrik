<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('pages.user.orders.index');
    }

    public function show(string $orderNumber): View|RedirectResponse
    {
        $order = Order::with(['details.productVariant.product.images', 'payment.refund'])->where('order_number', $orderNumber)->firstOrFail();

        try {
            $this->authorize('view', $order);

            return view('pages.user.orders.show', compact('order'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('orders.index');
        }
    }
}
