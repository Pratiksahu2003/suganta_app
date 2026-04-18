<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PaymentUpdateMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payment;
    public $status;
    public $reason;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment, string $status, ?string $reason = null, $user = null)
    {
        $this->payment = $payment;
        $this->status = $status;
        $this->reason = $reason;
        $this->user = $user ?? $payment->user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->getSubject();
        
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $invoiceUrl = null;
        if ($this->payment->status === 'success') {
            $invoiceUrl = URL::temporarySignedRoute(
                'payments.invoice',
                now()->addDays(7),
                ['orderId' => $this->payment->order_id]
            );
        }

        return new Content(
            view: 'emails.payment-update',
            with: [
                'subject' => $this->getSubject(),
                'title' => $this->getTitle(),
                'userName' => $this->user->name ?? 'Customer',
                'emailMessage' => $this->getMessage(),
                'status' => $this->payment->status,
                'orderId' => $this->payment->order_id,
                'transactionId' => $this->payment->reference_id,
                'amount' => $this->payment->amount,
                'currency' => $this->payment->currency,
                'paymentDate' => $this->payment->paid_at 
                    ? $this->payment->paid_at->format('F d, Y h:i A')
                    : $this->payment->created_at->format('F d, Y h:i A'),
                'paymentType' => $this->payment->meta['type'] ?? null,
                'reason' => $this->reason,
                'invoiceUrl' => $invoiceUrl,
                'actionUrl' => $this->getActionUrl(),
                'actionText' => $this->getActionText(),
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

    private function getSubject(): string
    {
        return match($this->status) {
            'initiated' => 'Payment Initiated - ' . config('app.name'),
            'pending' => 'Payment Pending - ' . config('app.name'),
            'success' => 'Payment Successful - ' . config('app.name'),
            'failed' => 'Payment Failed - ' . config('app.name'),
            'cancelled' => 'Payment Cancelled - ' . config('app.name'),
            'refunded' => 'Payment Refunded - ' . config('app.name'),
            default => 'Payment Notification - ' . config('app.name')
        };
    }

    private function getTitle(): string
    {
        return match($this->status) {
            'initiated' => 'Payment Initiated',
            'pending' => 'Payment Pending',
            'success' => 'Payment Successful',
            'failed' => 'Payment Failed',
            'cancelled' => 'Payment Cancelled',
            'refunded' => 'Payment Refunded',
            default => 'Payment Notification'
        };
    }

    private function getMessage(): string
    {
        $amount = $this->payment->currency . ' ' . number_format($this->payment->amount, 2);
        
        return match($this->status) {
            'initiated' => 'Your payment of ' . $amount . ' has been initiated. Please complete the payment to proceed.',
            'pending' => 'Your payment of ' . $amount . ' is pending confirmation. We will notify you once it is processed.',
            'success' => 'Your payment of ' . $amount . ' has been processed successfully. Thank you for your purchase!',
            'failed' => 'Your payment of ' . $amount . ' has failed.' . ($this->reason ? ' Reason: ' . $this->reason : ''),
            'cancelled' => 'Your payment of ' . $amount . ' has been cancelled.',
            'refunded' => 'Your payment of ' . $amount . ' has been refunded to your account.',
            default => 'Your payment status has been updated.'
        };
    }

    private function getActionUrl(): ?string
    {
        if ($this->status === 'success' && $this->payment->meta && isset($this->payment->meta['type']) && $this->payment->meta['type'] === 'subscription') {
            return route('user.portfolios.index');
        }
        
        return route('payments.success', ['order_id' => $this->payment->order_id]);
    }

    private function getActionText(): ?string
    {
        if ($this->status === 'success' && $this->payment->meta && isset($this->payment->meta['type']) && $this->payment->meta['type'] === 'subscription') {
            return 'View Portfolio';
        }
        
        return match($this->status) {
            'success' => 'View Payment Details',
            'failed', 'cancelled' => 'Try Again',
            default => 'View Details'
        };
    }
}

