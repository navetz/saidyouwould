<?php

namespace App\Mail;

use App\Models\OylChallenge;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ImmediateChallengeNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OylChallenge $challenge, public string $acknowledgementUrl)
    {
    }

    public function envelope(): Envelope
    {
        $sender = $this->challenge->sender_name ?: $this->challenge->sender_email;

        return new Envelope(subject: $sender.' put five dollars on your word');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.oyl-immediate');
    }
}
