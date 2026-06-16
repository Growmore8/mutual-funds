<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class NoReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int,string>  $lines
     */
    public function __construct(
        public string $subjectLine,
        public string $heading,
        public array $lines = [],
        public string $name = '',
        public ?string $actionUrl = null,
        public ?string $actionText = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $noReply = config('mail.from.address');

        return new Envelope(
            subject: $this->subjectLine,
            // No-reply: replies route back to the unattended sender address.
            replyTo: [new Address($noReply, config('mail.from.name') . ' (no-reply)')],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notice');
    }
}
