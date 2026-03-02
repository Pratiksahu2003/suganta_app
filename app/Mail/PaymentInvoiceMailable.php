<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PaymentInvoiceMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payment;
    public $user;
    public $invoiceUrl;
    public $companyInfo;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
        $this->user = $payment->user;
        $this->invoiceUrl = URL::temporarySignedRoute(
            'payments.invoice',
            now()->addDays(7),
            ['orderId' => $payment->order_id]
        );
        $this->companyInfo = config('company');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt & Invoice - ' . $this->payment->order_id . ' - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-invoice',
            with: [
                'paymentData' => $this->payment,
                'userData' => $this->user,
                'invoiceDownloadUrl' => $this->invoiceUrl,
                'companyData' => $this->companyInfo,
            ]
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

