<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
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
        $order = Order::with(['details.productVariant.product.images', 'payment', 'user.city.province'])->where('order_number', $orderNumber)->first();

        if (! $order) {
            session()->flash('error', 'Pesanan dengan nomor '.$orderNumber.' tidak ditemukan.');

            return redirect()->route('admin.orders.index');
        }

        try {
            $this->authorize('view', $order);

            return view('pages.admin.orders.show', compact('order'));
        } catch (AuthorizationException $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('admin.orders.index');
        }
    }
}
