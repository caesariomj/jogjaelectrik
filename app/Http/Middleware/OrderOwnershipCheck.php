<?php

namespace App\Http\Middleware;

use App\Models\Order;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class OrderOwnershipCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('orders.*')) {
            $order = $request->route('orderNumber') ?? ($request->route('order') ?? null);

            if (is_string($order)) {
                $order = Order::where('order_number', $order)->firstOrFail();
            }

            if (Gate::denies('view', $order ?? new Order)) {
                throw new HttpResponseException(
                    response()->view('errors.404', [], 404)
                );
            }
        }

        return $next($request);
    }
}
