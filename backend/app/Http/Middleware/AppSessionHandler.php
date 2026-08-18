<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AppSessionHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Keep campaign attribution for the eventual order. The values are stored
        // in the session so they survive the customer moving through checkout.
        $utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        $utm = [];
        foreach ($utmKeys as $key) {
            if ($request->filled($key)) {
                $utm[$key] = mb_substr((string) $request->input($key), 0, 180);
            }
        }
        if ($utm && $request->hasSession()) {
            $request->session()->put('order_utm', array_merge($request->session()->get('order_utm', []), $utm));
        }

        return $next($request);
    }
}