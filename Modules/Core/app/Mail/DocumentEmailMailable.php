<?php

declare(strict_types=1);

namespace Modules\Core\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class DocumentEmailMailable extends Mailable
{
    public function __construct(
        public readonly string $subjectLine,
        public readonly string $body,
        public readonly string $fromEmail,
        public readonly ?string $fromName,
        public readonly string $documentCode,
        public readonly string $attachmentDisk,
        public readonly string $attachmentPath,
        public readonly string $attachmentName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromEmail, $this->fromName),
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'core::mail.document',
            with: [
                'body' => $this->body,
                'documentCode' => $this->documentCode,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk($this->attachmentDisk, $this->attachmentPath)
                ->as($this->attachmentName)
                ->withMime('application/pdf'),
        ];
    }
}
