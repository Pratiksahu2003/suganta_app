<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Contact;

class ContactResponseMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;
    public $responseMessage;
    public $companyInfo;
    public $adminName;

    /**
     * Create a new message instance.
     */
    public function __construct(Contact $contact, $responseMessage = null, $adminName = null)
    {
        $this->contact = $contact;
        $this->responseMessage = $responseMessage ?? $contact->notes;
        $this->adminName = $adminName ?? 'SuGanta Support Team';
        $this->companyInfo = [
            'name' => config('company.name', 'SuGanta'),
            'email' => config('company.contact.email', config('mail.from.address')),
            'phone' => config('company.contact.phone'),
            'website' => config('company.contact.website', config('app.url')),
        ];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: ' . $this->contact->subject . ' - ' . $this->companyInfo['name'],
            from: config('mail.from.address'),
            replyTo: config('company.contact.email', config('mail.from.address')),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-response',
            with: [
                'contact' => $this->contact,
                'responseMessage' => $this->responseMessage,
                'companyInfo' => $this->companyInfo,
                'adminName' => $this->adminName,
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

