<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateXenditWebhookToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $xenditCallbackToken = $request->header('X-CALLBACK-TOKEN');
        $xenditWebhookToken = config('services.xendit.webhook_token');

        if (! $xenditCallbackToken || $xenditCallbackToken !== $xenditWebhookToken) {
            abort(404);
        }

        return $next($request);
    }
}
