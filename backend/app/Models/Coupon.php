<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'type', 'value', 'min_purchase',
        'valid_from', 'valid_to', 'status',
        // ব্যবহারের সীমা — null/0 মানে অসীম
        'usage_limit', 'usage_limit_per_customer',
    ];

    /**
     * used_count ইচ্ছে করে fillable-এর বাইরে রাখা হয়েছে — এটি শুধু
     * CouponService::recordUsage() থেকে atomic increment হবে, কোনো
     * অ্যাডমিন ফর্ম থেকে সরাসরি সেট হবে না।
     */
    public function usages()
    {
        return $this->hasMany(CouponUsage::class, 'coupon_id');
    }

    /**
     * আর কতবার ব্যবহার করা যাবে। null = অসীম।
     */
    public function getRemainingUsesAttribute(): ?int
    {
        $limit = (int) ($this->usage_limit ?? 0);

        if ($limit < 1) {
            return null;
        }

        return max(0, $limit - (int) ($this->used_count ?? 0));
    }
}
