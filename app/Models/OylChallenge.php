<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OylChallenge extends Model
{
    protected $table = 'oyl_challenges';

    protected $guarded = [];

    protected $hidden = [
        'recipient_token_hash',
        'recipient_token_encrypted',
        'video_storage_key',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'notify_recipient' => 'boolean',
            'paid_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function response(): HasOne
    {
        return $this->hasOne(OylRecipientResponse::class, 'challenge_id');
    }

    public function emailEvents(): HasMany
    {
        return $this->hasMany(OylEmailEvent::class, 'challenge_id');
    }
}
