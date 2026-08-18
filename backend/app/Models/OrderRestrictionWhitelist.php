<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * অর্ডার সীমার আওতামুক্ত ফোন নম্বর (রিসেলার, পাইকারি ক্রেতা, নিজেদের টেস্ট নম্বর)।
 */
class OrderRestrictionWhitelist extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * ফোন নম্বর মেলানোর আগে স্বাভাবিক রূপে আনি — +8801..., 8801..., 01...
     * সবগুলোকে শেষ ১১ ডিজিটে নামিয়ে আনা হয়।
     */
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return '';
        }

        return strlen($digits) > 11 ? substr($digits, -11) : $digits;
    }

    public static function has(?string $phone): bool
    {
        $normalized = self::normalize($phone);

        if ($normalized === '') {
            return false;
        }

        // ডেটাবেসে যে ফরম্যাটেই সেভ থাকুক, শেষ ১১ ডিজিট মিলিয়ে দেখি
        return self::query()
            ->where('phone', $normalized)
            ->orWhere('phone', 'like', '%' . $normalized)
            ->exists();
    }
}
