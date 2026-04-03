<?php

namespace App\Services;

use App\Models\Note;
use App\Models\NotePurchase;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotePurchaseService
{
    public function __construct(
        protected CashfreeService $cashfree
    ) {}

    /**
     * Get or create a note purchase payment checkout URL.
     *
     * @return array{success: bool, checkout_url?: string, already_paid?: bool, message?: string, order_id?: string, payment_session_id?: string, note_name?: string, amount?: float, currency?: string}
     */
    public function getOrCreateNotePurchaseCheckoutUrl(User $user, Note $note, string $source = 'api'): array
    {
        if (!$note->is_paid || $note->price <= 0) {
            return [
                'success' => false,
                'message' => 'This note is free and does not require payment.',
            ];
        }

        if ($note->isPurchasedBy($user->id)) {
            return [
                'success' => false,
                'message' => 'You have already purchased this note.',
                'already_paid' => true,
            ];
        }

        if (!$user->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'Please verify your email address before purchasing notes.',
            ];
        }

        if (empty(config('cashfree.app_id')) || empty(config('cashfree.secret_key'))) {
            return [
                'success' => false,
                'message' => 'Payment system is not configured. Please contact the administrator.',
            ];
        }

        // Check for existing pending payment for this note
        $existingPayment = Payment::where('user_id', $user->id)
            ->where('meta->type', 'note')
            ->where('meta->note_id', $note->id)
            ->whereIn('status', ['created', 'pending'])
            ->latest()
            ->first();

        if ($existingPayment) {
            try {
                $freshOrder = $this->cashfree->getOrder($existingPayment->order_id);
                $orderStatus = strtoupper($freshOrder['order_status'] ?? '');

                if ($this->cashfree->isOrderPaid($freshOrder)) {
                    $this->processSuccessfulPayment($existingPayment, $freshOrder);
                    return ['success' => true, 'already_paid' => true];
                }

                if (in_array($orderStatus, ['ACTIVE'], true)) {
                    $existingPayment->update(['gateway_response' => $freshOrder]);
                    $checkoutUrl = $this->buildProxyCheckoutUrl($existingPayment->order_id);

                    return [
                        'success' => true,
                        'checkout_url' => $checkoutUrl,
                        'payment_session_id' => $freshOrder['payment_session_id'] ?? null,
                        'order_id' => $existingPayment->order_id,
                        'note_name' => $note->name,
                        'amount' => (float) $note->price,
                        'currency' => 'INR',
                    ];
                }

                $existingPayment->update(['status' => 'failed', 'gateway_response' => $freshOrder]);
            } catch (\Exception $e) {
                Log::error('Failed to refresh existing note purchase payment order', [
                    'payment_id' => $existingPayment->id,
                    'order_id' => $existingPayment->order_id,
                    'error' => $e->getMessage(),
                ]);
                $existingPayment->update(['status' => 'failed']);
            }
        }

        // Create new payment order
        $orderId = 'NOTE_' . Str::upper(Str::random(10));
        $amount = (float) $note->price;
        $currency = 'INR';

        $payment = Payment::create([
            'order_id' => $orderId,
            'user_id' => $user->id,
            'currency' => $currency,
            'amount' => $amount,
            'status' => 'created',
            'meta' => [
                'type' => 'note',
                'note_id' => $note->id,
                'note_name' => $note->name,
                'source' => $source,
            ],
        ]);

        try {
            $orderPayload = $this->cashfree->buildOrderPayload(
                $orderId,
                (string) $user->id,
                $user->email,
                $user->phone ?? '9999999999',
                $amount,
                $currency,
                $user->name ?? ''
            );

            $orderResponse = $this->cashfree->createOrder($orderPayload);
            $checkoutUrl = $this->buildProxyCheckoutUrl($orderId);

            $payment->update([
                'reference_id' => $orderResponse['cf_order_id'] ?? $orderId,
                'gateway_response' => $orderResponse,
                'status' => 'pending',
            ]);

            Log::info('Note purchase payment initiated', [
                'order_id' => $orderId,
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'note_id' => $note->id,
                'amount' => $amount,
                'source' => $source,
            ]);

            return [
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'payment_session_id' => $orderResponse['payment_session_id'] ?? null,
                'order_id' => $orderId,
                'note_name' => $note->name,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (\Exception $e) {
            Log::error('Note purchase payment initiation failed', [
                'order_id' => $orderId,
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'note_id' => $note->id,
                'error' => $e->getMessage(),
            ]);

            $payment->update(['status' => 'failed']);

            return [
                'success' => false,
                'message' => 'Failed to create payment order. Please try again.',
            ];
        }
    }

    /**
     * Process a successful note purchase payment.
     */
    public function processSuccessfulPayment(Payment $payment, array $gatewayResponse = []): NotePurchase
    {
        // 1. Idempotency Check
        if ($payment->status === 'success') {
            $existing = NotePurchase::where('payment_id', $payment->id)->first();
            if ($existing) return $existing;
        }

        return DB::transaction(function () use ($payment, $gatewayResponse) {
            $payment->update([
                'status' => 'success',
                'gateway_response' => $gatewayResponse,
                'processed_at' => now(),
            ]);

            $noteId = $payment->meta['note_id'] ?? null;
            if (!$noteId) {
                throw new \Exception('Note ID not found in payment meta data.');
            }

            $note = Note::find($noteId);
            if (!$note) {
                throw new \Exception('Note not found.');
            }

            $notePurchase = NotePurchase::updateOrCreate(
                [
                    'user_id' => $payment->user_id,
                    'note_id' => $note->id,
                ],
                [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'status' => 'completed',
                    'download_count' => 0,
                    'purchased_at' => now(),
                ]
            );

            Log::info('Note purchase completed successfully', [
                'note_purchase_id' => $notePurchase->id,
                'user_id' => $payment->user_id,
                'note_id' => $note->id,
                'payment_id' => $payment->id,
            ]);

            return $notePurchase;
        });
    }

    /**
     * Get fresh checkout data for an existing note payment.
     */
    public function getFreshCheckoutData(Payment $payment): ?array
    {
        if ($payment->status === 'success') {
            return ['already_paid' => true];
        }

        if (!in_array($payment->status, ['created', 'pending'], true)) {
            return null;
        }

        try {
            $freshOrder = $this->cashfree->getOrder($payment->order_id);
            $orderStatus = strtoupper($freshOrder['order_status'] ?? '');

            if ($this->cashfree->isOrderPaid($freshOrder)) {
                if (($payment->meta['type'] ?? '') === 'note') {
                    $this->processSuccessfulPayment($payment, $freshOrder);
                }
                return ['already_paid' => true];
            }

            if (in_array($orderStatus, ['ACTIVE'], true)) {
                $payment->update(['gateway_response' => $freshOrder]);
                $checkoutUrl = $this->cashfree->getCheckoutUrl($freshOrder);

                if ($checkoutUrl) {
                    return [
                        'checkout_url' => $checkoutUrl,
                        'payment_session_id' => $freshOrder['payment_session_id'] ?? null,
                    ];
                }
            }

            $payment->update(['status' => 'failed', 'gateway_response' => $freshOrder]);
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get fresh checkout data for note payment', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildProxyCheckoutUrl(string $orderId): string
    {
        $parsed = parse_url(rtrim(config('app.url', 'http://localhost'), '/'));
        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'localhost');

        if (!empty($parsed['port'])) {
            $baseUrl .= ':' . $parsed['port'];
        }

        return $baseUrl . '/api/v1/payment/checkout?order_id=' . $orderId;
    }
}
