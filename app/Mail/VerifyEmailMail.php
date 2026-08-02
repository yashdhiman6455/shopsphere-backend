<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your ShopSphere email address',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify_email',
            with: [
                'user' => $this->user,
                'url' => $this->verificationUrl(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    public function verificationUrl(): string
    {
        $hash = sha1($this->user->getEmailForVerification());
        $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');

        return "{$frontendUrl}/verify-email/{$this->user->id}/{$hash}";
    }
}
