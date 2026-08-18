<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\SmsGateway;
use Illuminate\Support\Facades\Log;

/**
 * অর্ডার সংক্রান্ত নোটিফিকেশন (SMS)।
 *
 * আগে `CustomerController::order_save()`-এর ভিতরে ইনলাইন কোড ছিল। কুইক-অর্ডার
 * পপআপ থেকেও একই নোটিফিকেশন যাওয়ার জন্য লজিকটা এখানে বের করে আনা হয়েছে —
 * চেকআউট ও কুইক-অর্ডার, দুজায়গাতেই একই ফরম্যাটের SMS যায়।
 */
class OrderNotificationService
{
    /**
     * কাস্টমারকে অর্ডার কনফার্মেশন SMS পাঠায়।
     */
    public function orderSms(Order $order, ?string $customerPhone, ?string $customerName): void
    {
        try {
            $sms_gateway = SmsGateway::where(['status' => 1, 'order' => 1])->first();
            if (!$sms_gateway) {
                $sms_gateway = SmsGateway::where('status', 1)->first();
            }

            if (!$sms_gateway) {
                return;
            }

            $url = $sms_gateway->url;

            $phone = $customerPhone ?: (optional($order->shipping)->phone ?: optional($order->customer)->phone);
            $name  = $customerName  ?: (optional($order->shipping)->name  ?: optional($order->customer)->name);
            $site  = GeneralSetting::where('status', 1)->first();

            if (!$phone) {
                Log::warning("Customer SMS skipped: no phone for order {$order->id}");
                return;
            }

            $message = "প্রিয় {$name}! আপনার অর্ডার #{$order->invoice_id} সফলভাবে গ্রহণ করা হয়েছে। মোট: {$order->amount} Tk. " . optional($site)->name;

            $postData = [
                'api_key' => $sms_gateway->api_key,
                'number'  => preg_replace('/[^0-9+]/', '', $phone),
                'type'    => 'text',
                'senderid'=> $sms_gateway->serderid ?? $sms_gateway->senderid ?? '',
                'message' => $message,
            ];

            $resp = $this->curlPost($url, $postData);

            Log::info("Customer SMS to {$phone}: resp=" . substr((string) $resp, 0, 200));
        } catch (\Exception $e) {
            Log::error("Customer SMS error for order {$order->id}: " . $e->getMessage());
        }
    }

    /**
     * অ্যাডমিনকে নতুন অর্ডারের SMS পাঠায়।
     */
    public function adminOrderSms(Order $order, ?string $customerName, ?string $customerPhone): void
    {
        try {
            $sms_gateway = SmsGateway::where('status', 1)->first();
            if (!$sms_gateway) {
                return;
            }

            $url = $sms_gateway->url;

            $adminPhones = env('ADMIN_PHONE_LIST', null);
            if (!$adminPhones && isset($sms_gateway->admin_phone)) {
                $adminPhones = $sms_gateway->admin_phone;
            }
            if (!$adminPhones) {
                $contact = Contact::first();
                $adminPhones = $contact->phone ?? null;
            }

            if (!$adminPhones) {
                return;
            }

            $site = GeneralSetting::where('status', 1)->first();
            $name = $customerName ?: (optional($order->shipping)->name  ?: optional($order->customer)->name);
            $phone = $customerPhone ?: (optional($order->shipping)->phone ?: optional($order->customer)->phone);

            $message = "নতুন অর্ডার এসেছে!\nOrder#: {$order->invoice_id}\nকাস্টমার: {$name}\nমোবাইল: {$phone}\nমোট: {$order->amount} Tk " . optional($site)->name;

            $numbers = array_filter(array_map('trim', explode(',', (string) $adminPhones)));
            foreach ($numbers as $adminPhone) {
                $adminPhone = preg_replace('/[^0-9+]/', '', $adminPhone);
                if ($adminPhone === '') {
                    continue;
                }

                $postData = [
                    'api_key' => $sms_gateway->api_key,
                    'number'  => $adminPhone,
                    'type'    => 'text',
                    'senderid'=> $sms_gateway->serderid ?? $sms_gateway->senderid ?? '',
                    'message' => $message,
                ];

                $resp = $this->curlPost($url, $postData);

                Log::info("Admin SMS to {$adminPhone}: resp=" . substr((string) $resp, 0, 200));
            }
        } catch (\Exception $e) {
            Log::error('Admin SMS send failed: ' . $e->getMessage());
        }
    }

    /**
     * SMS গেটওয়েতে POST পাঠায়।
     */
    protected function curlPost(string $url, array $postData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        curl_close($ch);

        return $resp;
    }
}
