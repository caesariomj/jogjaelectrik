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
            $this->authorize('viewAny', new Order);

            return view('pages.admin.orders.index');
        } catch (AuthorizationException $authException) {
            session()->flash('error', $authException->getMessage());

            return redirect()->route('home');
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('An unexpected error occurred when admin tried to access order management page', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return redirect()->route('home');
        }
    }

    public function show(string $orderNumber): View|RedirectResponse
    {
        $order = Order::with(['details.productVariant.product.images', 'payment', 'user.city.province'])->where('order_number', $orderNumber)->first();

        if (! $order) {
            session()->flash('error', 'Data pesanan dengan nomor '.$orderNumber.' tidak ditemukan.');

            return redirect()->route('admin.orders.index');
        }

        try {
            $this->authorize('view', $order);

            return view('pages.admin.orders.show', compact('order'));
        } catch (AuthorizationException $authException) {
            session()->flash('error', $authException->getMessage());

            return redirect()->route('home');
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('An unexpected error occurred when admin tried to access order management page', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return redirect()->route('home');
        }
    }
}
