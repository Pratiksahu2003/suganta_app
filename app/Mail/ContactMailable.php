<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Contact;

class ContactMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;
    public $companyInfo;

    /**
     * Create a new message instance.
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
        $this->companyInfo = [
            'name' => config('company.name'),
            'email' => config('company.contact.email'),
            'phone' => config('company.contact.phone'),
            'website' => config('company.contact.website'),
        ];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Form Submission - ' . $this->contact->subject,
            from: config('mail.from.address'),
            replyTo: $this->contact->email,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form',
            with: [
                'contact' => $this->contact,
                'companyInfo' => $this->companyInfo,
            ],
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
