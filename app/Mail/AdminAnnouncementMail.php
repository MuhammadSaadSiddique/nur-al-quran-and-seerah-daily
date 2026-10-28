<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class AdminAnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $announcementSubject;
    public string $announcementContent;
    public User $user;
    public ?string $actionUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $content, User $user, ?string $actionUrl = null)
    {
        $this->announcementSubject = $subject;
        $this->announcementContent = $content;
        $this->user = $user;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->announcementSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.announcement',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
