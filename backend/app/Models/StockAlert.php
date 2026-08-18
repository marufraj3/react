<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * স্টক ফুরিয়ে যাওয়া বা প্রায় শেষ হওয়ার অ্যালার্ট।
 */
class StockAlert extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_read'     => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'variant_price_id');
    }

    /** এখনো সমাধান হয়নি এমন অ্যালার্ট */
    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'low_stock' ? 'স্টক প্রায় শেষ' : 'স্টক আউট';
    }

    public function getTypeClassAttribute(): string
    {
        return $this->type === 'low_stock' ? 'warning' : 'danger';
    }
}
