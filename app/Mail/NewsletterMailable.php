<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\BlogPost;
use App\Models\Newsletter;

class NewsletterMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $blogPost;
    public $subscriber;
    public $companyInfo;
    public $unsubscribeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(BlogPost $blogPost, Newsletter $subscriber)
    {
        $this->blogPost = $blogPost;
        $this->subscriber = $subscriber;
        $this->companyInfo = [
            'name' => config('company.name', 'SuGanta'),
            'email' => config('company.contact.email'),
            'phone' => config('company.contact.phone'),
            'website' => config('company.contact.website'),
        ];
        $this->unsubscribeUrl = route('newsletter.unsubscribe', ['email' => $subscriber->email]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Blog Post: ' . $this->blogPost->title,
            from: config('mail.from.address'),
            replyTo: config('company.contact.email'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter',
            with: [
                'blogPost' => $this->blogPost,
                'subscriber' => $this->subscriber,
                'companyInfo' => $this->companyInfo,
                'unsubscribeUrl' => $this->unsubscribeUrl,
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
