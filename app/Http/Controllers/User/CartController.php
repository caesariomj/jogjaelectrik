<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Display a listing of the cart.
     */
    public function index(): View|RedirectResponse
    {
        $cart = auth()->check() ? auth()->user()->cart()->firstOrCreate() : null;

        $productRecommendations = [];

        if ($cart && $cart->items()->exists()) {
            $cartItems = $cart->items()->with('productVariant.product.subcategory')->get();

            $cartItemCategoryIds = $cartItems->pluck('productVariant.product.subcategory.category_id');

            $cartItemProductIds = $cartItems->pluck('productVariant.product.id');

            $productRecommendations = Product::whereHas('subcategory', function ($query) use ($cartItemCategoryIds) {
                $query->whereIn('category_id', $cartItemCategoryIds);
            })
                ->whereNotIn('id', $cartItemProductIds)
                ->active()
                ->limit(6)
                ->get();

            $productRecommendations = ProductResource::collection($productRecommendations)->toArray(request());
        }

        try {
            $this->authorize('view', $cart);

            return view('pages.user.cart', compact('productRecommendations'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $authException) {
            $errorMessage = $authException->getMessage();

            if ($authException->getCode() === 401) {
                session()->flash('error', $errorMessage);

                return redirect()->route('login');
            }

            session()->flash('error', $errorMessage);

            return redirect()->route('home');
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error('An unexpected error occurred when the user tried to access his shopping cart page', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            session('error', 'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.');

            return redirect()->route('home');
        }
    }
}
