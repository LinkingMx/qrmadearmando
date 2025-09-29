<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    use HasUuids;

    protected $fillable = [
        'legacy_id',
        'user_id',
        'status',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'expiry_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
