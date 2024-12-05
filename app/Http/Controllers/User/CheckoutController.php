<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = auth()->check() ? auth()->user()->cart()->firstOrCreate() : null;

        if (! $cart || ! $cart->items()->exists()) {
            session()->flash('error', 'Keranjang belanja Anda kosong. Tambahkan produk ke keranjang sebelum melanjutkan ke proses checkout.');

            return redirect()->route('cart');
        }

        if (intval($cart->calculateTotalWeight()) >= 30000) {
            session()->flash('error', 'Total berat produk di keranjang Anda melebihi batas maksimum 30.000 gram (30 kg).');

            return redirect()->route('cart');
        }

        try {
            $this->authorize('checkout', $cart);

            return view('pages.user.checkout', compact('cart'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            $errorMessage = $authException->getMessage();

            if ($authException->getCode() === 401) {
                session()->flash('error', $errorMessage);

                return redirect()->route('login');
            }

            session()->flash('error', $errorMessage);

            return redirect()->route('home');
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('An unexpected error occurred when the user tried to access checkout page', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return redirect()->route('home');
        }
    }
}
