<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
            'anonymous' => 'boolean',
            'is_public' => 'boolean',
            'paid_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * The sender name as the recipient is allowed to see it.
     */
    public function publicSenderName(): ?string
    {
        return $this->anonymous ? null : $this->sender_name;
    }

    /** Paid and opted onto the public board. */
    public function scopeOnBoard(Builder $query): Builder
    {
        return $query->where('is_public', true)->where('payment_status', 'paid');
    }

    public function publicUrl(): string
    {
        return url('/p/'.$this->public_slug);
    }

    /**
     * Everything the public board is allowed to show. Emails, the video key,
     * and the recipient's name never leave here.
     */
    public function boardData(): array
    {
        return [
            'slug' => $this->public_slug,
            'url' => $this->publicUrl(),
            'goal_title' => $this->goal_title,
            'sender_name' => $this->publicSenderName(),
            'anonymous' => (bool) $this->anonymous,
            'mode' => $this->mode,
            'written_terms' => $this->written_terms,
            'delivery_date' => $this->delivery_date->toDateString(),
            'sealed_on' => ($this->paid_at ?? $this->created_at)->toDateString(),
            'delivered' => $this->status === 'delivered',
            'response' => $this->relationLoaded('response') && $this->response ? [
                'response' => $this->response->response,
                'response_note' => $this->response->response_note,
            ] : null,
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
