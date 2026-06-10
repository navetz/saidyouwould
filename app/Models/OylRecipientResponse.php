<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OylRecipientResponse extends Model
{
    protected $table = 'oyl_recipient_responses';

    protected $guarded = [];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(OylChallenge::class, 'challenge_id');
    }
}
