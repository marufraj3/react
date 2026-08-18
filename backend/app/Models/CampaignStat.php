<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * এক ক্যাম্পেইনের এক দিনের পারফরম্যান্স কাউন্টার।
 */
class CampaignStat extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'revenue'   => 'float',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }
}
