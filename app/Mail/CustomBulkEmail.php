<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class CustomBulkEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $userEmail;
    public $userName;
    public $userId;
    public $isCustomEmail;
    public $subject;
    public $content;
    public $companyInfo;

    /**
     * Create a new message instance.
     * 
     * @param User|object $user User object or object with 'email' and 'name' properties
     * @param string $subject Email subject
     * @param string $content Email content
     */
    public function __construct($user, string $subject, string $content)
    {
        // Handle serialization properly for both User models and plain objects
        if ($user instanceof User) {
            $this->user = $user;
            $this->isCustomEmail = false;
            $this->userEmail = null;
            $this->userName = null;
            $this->userId = null;
        } else {
            // Custom email - store as plain properties (serializable)
            $this->user = null;
            $this->isCustomEmail = true;
            $this->userEmail = $user->email ?? null;
            $this->userName = $user->name ?? 'Customer';
            $this->userId = $user->id ?? null;
        }
        
        $this->subject = $subject;
        $this->content = $content;
        $this->companyInfo = [
            'name' => config('mail.from.name', 'SuGanta Tutors'),
            'email' => config('company.contact.email'),
            'phone' => config('company.contact.phone'),
            'website' => config('company.contact.website'),
            'support_email' => config('company.contact.support_email'),
            'address' => config('company.address'),
            'social' => config('company.social'),
            'business_hours' => config('company.business_hours'),
        ];
    }
    
    /**
     * Get the user object (reconstruct if needed for custom emails)
     */
    protected function getUser()
    {
        if ($this->isCustomEmail) {
            // Reconstruct the user object for custom emails
            return (object) [
                'id' => $this->userId,
                'name' => $this->userName,
                'email' => $this->userEmail,
            ];
        }
        
        return $this->user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', 'info@suganta.co');
        $fromName = config('mail.from.name', 'SuGanta Tutors');
        $replyToAddress = config('company.contact.email', $fromAddress);
        
        return new Envelope(
            subject: $this->subject,
            from: new Address(
                $fromAddress,
                $fromName
            ),
            replyTo: [
                new Address(
                    $replyToAddress,
                    $fromName
                ),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Get the user object (reconstruct if needed)
        $user = $this->getUser();
        
        return new Content(
            view: 'emails.custom-bulk-email',
            with: [
                'user' => $user,
                'subject' => $this->subject,
                'content' => $this->content,
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

