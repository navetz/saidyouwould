<?php

namespace App\Mail;

use App\Models\OylChallenge;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChallengeSealedReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OylChallenge $challenge)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sealed until '.$this->challenge->delivery_date->format('F j, Y')
                .($this->challenge->goal_title ? ': '.$this->challenge->goal_title : ''),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.oyl-receipt');
    }
}
