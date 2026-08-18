<?php

// Application-wide helpers can be registered here. The retired reseller landing URL
// helper was removed; this file remains because Composer autoloads it explicitly.

if (! function_exists('storefront_return_url')) {
    /**
     * Build the React storefront return URL for a payment callback.
     *
     * When STORE_URL is not configured this returns null and the caller falls
     * back to the existing Laravel Blade pages (customer.order_success, home…).
     *
     * @param  mixed  $order  Order model (or null for cancel/fail without an order)
     * @param  string $status success | cancelled | failed
     */
    function storefront_return_url($order = null, string $status = 'success'): ?string
    {
        $storeUrl = rtrim((string) config('app.store_url'), '/');

        if ($storeUrl === '') {
            return null;
        }

        $invoice = null;
        if ($order) {
            $invoice = $order->invoice_id ?? $order->id ?? null;
        }

        return $storeUrl.'?'.http_build_query([
            'payment' => $status,
            'order'   => $invoice,
        ]);
    }
}
