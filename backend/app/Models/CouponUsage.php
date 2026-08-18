<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * কোন কুপন কে কখন ব্যবহার করল তার রেকর্ড।
 * প্রতি-কাস্টমার সীমা এখান থেকেই গোনা হয়।
 */
class CouponUsage extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'discount' => 'float',
        ];
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
