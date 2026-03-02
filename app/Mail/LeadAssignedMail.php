<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead)
    {
    }

    public function build()
    {
        return $this->subject('New Lead Assigned: ' . ($this->lead->lead_id ?? $this->lead->id))
            ->view('emails.leads.assigned', [
                'lead' => $this->lead,
                'url' => route('admin.leads.show', $this->lead),
                'logoUrl' => asset('logo/logo.png'),
            ]);
    }
}


