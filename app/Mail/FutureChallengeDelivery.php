<?php

namespace App\Mail;

use App\Models\OylChallenge;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FutureChallengeDelivery extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OylChallenge $challenge, public string $deliveryUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->challenge->goal_title
            ? 'You said you would: '.$this->challenge->goal_title
            : 'You said you would. The year is up.');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.oyl-delivery');
    }
}
