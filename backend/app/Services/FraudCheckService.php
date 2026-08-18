<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * কুরিয়ার ফ্রড/সাকসেস রেশিও চেক।
 *
 * আগে এই লজিকটা শুধু Admin\OrderController@fraudCheck() এর ভেতরে ছিল, তাই
 * ম্যানুয়ালি বাটনে ক্লিক না করলে কখনো চলত না। এখন এটি একটি সার্ভিস — অ্যাডমিন
 * প্যানেলের ম্যানুয়াল চেক আর নতুন অর্ডারের অটো-চেক দুটোই একই কোড ব্যবহার করে।
 */
class FraudCheckService
{
    private const API_URL = 'https://www.creativedesign.com.bd/api/v1/check-fraud';

    /**
     * একটি মোবাইল নম্বরের ফ্রড রিপোর্ট আনবে এবং ওই নম্বরের সব অর্ডারে সেভ করবে।
     *
     * @return array{status: string, message?: string, data?: array}
     */
    public function checkPhone(?string $mobile, int $timeout = 20): array
    {
        $mobile = trim((string) $mobile);

        if ($mobile === '') {
            return ['status' => 'failed', 'message' => 'Mobile number missing'];
        }

        $apiKey = $this->apiKey();

        if (!$apiKey) {
            return ['status' => 'failed', 'message' => 'Fraud API Key missing'];
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'x-api-key'    => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::API_URL, ['phone' => $mobile]);

            $res = $response->json();

            if (!is_array($res) || !isset($res['status']) || $res['status'] !== 'success') {
                return [
                    'status'  => 'failed',
                    'message' => is_array($res) && isset($res['message'])
                        ? $res['message']
                        : 'Fraud check ব্যর্থ হয়েছে',
                ];
            }

            $this->applyToOrders($mobile, $res);

            return ['status' => 'success', 'data' => $res];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'API Error: ' . $e->getMessage()];
        }
    }

    /**
     * অর্ডার তৈরি হওয়ার পর ব্যাকগ্রাউন্ডে (response পাঠানোর পর) চেক চালায়।
     * কাস্টমারকে কখনোই অপেক্ষা করানো হয় না — ব্যর্থ হলে শুধু লগ হয়।
     */
    public function queueAfterResponse(?string $mobile, ?int $orderId = null): void
    {
        $mobile = trim((string) $mobile);

        if ($mobile === '' || !$this->apiKey()) {
            return;
        }

        register_shutdown_function(function () use ($mobile, $orderId) {
            try {
                $result = $this->checkPhone($mobile, 12);

                if (($result['status'] ?? '') !== 'success') {
                    Log::warning('Auto fraud check skipped for order ' . ($orderId ?? '-') . ': ' . ($result['message'] ?? 'unknown'));
                }
            } catch (\Throwable $e) {
                Log::error('Auto fraud check failed for order ' . ($orderId ?? '-') . ': ' . $e->getMessage());
            }
        });
    }

    private function apiKey(): ?string
    {
        $generalSetting = GeneralSetting::where('status', 1)->first() ?: GeneralSetting::first();
        $apiKey = $generalSetting->fraud_api_key ?? null;

        return $apiKey ? (string) $apiKey : null;
    }

    /**
     * একই নম্বরের সব অর্ডারে কুরিয়ারভিত্তিক সাকসেস/ক্যান্সেল ডাটা বসায়।
     */
    private function applyToOrders(string $mobile, array $res): void
    {
        $orders = Order::whereHas('shipping', fn ($q) => $q->where('phone', $mobile))->get();

        if ($orders->isEmpty()) {
            return;
        }

        foreach ($orders as $order) {
            if (isset($res['is_fraud']) && $res['is_fraud'] === true) {
                $order->fraud_rate = 0;
            } elseif (isset($res['data']) && is_array($res['data'])) {
                $cData = $res['data'];

                foreach (['pathao', 'redx', 'steadfast'] as $courier) {
                    $order->{$courier . '_success'} = $cData[$courier]['success_parcel'] ?? 0;
                    $order->{$courier . '_cancel'}  = $cData[$courier]['cancelled_parcel'] ?? 0;
                    $order->{$courier . '_rate'}    = $cData[$courier]['success_ratio'] ?? 0;
                }

                if (isset($cData['summary'])) {
                    $order->fraud_success = $cData['summary']['success_parcel'] ?? 0;
                    $order->fraud_cancel  = $cData['summary']['cancelled_parcel'] ?? 0;
                    $order->fraud_rate    = $cData['summary']['success_ratio'] ?? 0;
                }
            }

            $order->save();
        }
    }
}
