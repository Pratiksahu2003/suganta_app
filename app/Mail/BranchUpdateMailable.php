<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Branch;
use App\Models\Institute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BranchUpdateMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $branchManager;
    public $branch;
    public $institute;
    public $updatedFields;

    /**
     * Create a new message instance.
     */
    public function __construct(User $branchManager, Branch $branch, Institute $institute, array $updatedFields)
    {
        $this->branchManager = $branchManager;
        $this->branch = $branch;
        $this->institute = $institute;
        $this->updatedFields = $updatedFields;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Branch Information Updated - ' . $this->branch->branch_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.branch-update',
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
