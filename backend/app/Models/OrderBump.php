<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderBump extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'discount_value'  => 'float',
            'min_cart_amount' => 'float',
            'status'          => 'boolean',
            'sort_order'      => 'integer',
            'impressions'     => 'integer',
            'conversions'     => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * অফারের দাম — প্রোডাক্টের নিয়মিত দাম থেকে ছাড় বাদ দিয়ে।
     */
    public function offerPrice(): float
    {
        $base = (float) ($this->product->new_price ?? $this->product->old_price ?? 0);

        $discount = $this->discount_type === 'percent'
            ? $base * ($this->discount_value / 100)
            : $this->discount_value;

        return round(max(0, $base - $discount), 2);
    }

    /**
     * কাস্টমার কত টাকা সাশ্রয় করছে।
     */
    public function savings(): float
    {
        $base = (float) ($this->product->new_price ?? $this->product->old_price ?? 0);

        return round(max(0, $base - $this->offerPrice()), 2);
    }
}
