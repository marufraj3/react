<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeminiChat extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopeForAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
