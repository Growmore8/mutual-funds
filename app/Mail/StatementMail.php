<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatementMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string,mixed>  $data
     * @param  string|null  $pdfBytes  Raw PDF bytes to attach (null if no PDF engine).
     */
    public function __construct(public array $data, public ?string $pdfBytes = null, public string $viewName = 'emails.statement', public ?string $subjectLine = null)
    {
    }

    public function envelope(): Envelope
    {
        $noReply = config('mail.from.address');

        return new Envelope(
            subject: $this->subjectLine ?? ('Your GrowthCapital Mutual Fund statement · ' . $this->data['label']),
            replyTo: [new Address($noReply, config('mail.from.name') . ' (no-reply)')],
        );
    }

    public function content(): Content
    {
        return new Content(view: $this->viewName);
    }

    public function attachments(): array
    {
        if (! $this->pdfBytes) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->pdfBytes, 'GrowthCapital-Statement-' . $this->data['code'] . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
