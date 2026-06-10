<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OylEmailEvent extends Model
{
    protected $table = 'oyl_email_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(OylChallenge::class, 'challenge_id');
    }
}
