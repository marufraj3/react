<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'custom_page_published_at' => 'datetime',
        ];
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id')
            ->select(
                'products.id',
                'products.name',
                'products.slug',
                'products.old_price',
                'products.new_price'
            );
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'campaign_product',
            'campaign_id',
            'product_id'
        )->select(
            'products.id',
            'products.name',
            'products.slug',
            'products.old_price',
            'products.new_price'
        );
    }

    public function images()
    {
        return $this->hasMany(CampaignReview::class, 'campaign_id')
            ->select(
                'id',
                'image',
                'campaign_id'
            );
    }

    public function orderBumps()
    {
        return $this->hasMany(OrderBump::class, 'campaign_id');
    }

    public function isCustomPageLive(): bool
    {
        return $this->custom_page_published_at !== null
            && filled($this->custom_html);
    }
}
