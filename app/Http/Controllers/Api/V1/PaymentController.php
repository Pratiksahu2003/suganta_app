<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends BaseApiController
{
    /**
     * Get authenticated user's payment history.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Payment::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $payments = $query->paginate($perPage);

        $data = $payments->through(function (Payment $payment) {
            return $this->formatPaymentForResponse($payment);
        });

        return $this->success('Payment history retrieved successfully.', [
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get invoice URL for a successful payment.
     */
    public function invoice(string $orderId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $payment = Payment::query()
            ->where('order_id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$payment) {
            return $this->notFound('Payment not found or access denied.');
        }

        if ($payment->status !== 'success') {
            return $this->error(
                'Invoice is only available for successful payments.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $invoiceUrl = $this->generateInvoiceUrl($payment->order_id);

        return $this->success('Invoice URL generated successfully.', [
            'order_id' => $payment->order_id,
            'invoice_url' => $invoiceUrl,
            'expires_at' => now()->addDays(config('invoice.expires_days', 7))->toIso8601String(),
        ]);
    }

    /**
     * Format payment for API response.
     */
    private function formatPaymentForResponse(Payment $payment): array
    {
        $data = [
            'id' => $payment->id,
            'order_id' => $payment->order_id,
            'reference_id' => $payment->reference_id,
            'currency' => $payment->currency,
            'amount' => (float) $payment->amount,
            'status' => $payment->status,
            'created_at' => $payment->created_at->toIso8601String(),
            'processed_at' => $payment->processed_at?->toIso8601String(),
        ];

        if ($payment->status === 'success') {
            $data['invoice_url'] = $this->generateInvoiceUrl($payment->order_id);
        }

        return $data;
    }

    /**
     * Generate a signed temporary invoice URL.
     */
    private function generateInvoiceUrl(string $orderId): string
    {
        $baseUrl = rtrim(config('invoice.base_url'), '/');
        $expiration = now()->addDays(config('invoice.expires_days', 7));
        $path = '/payment/invoice/' . $orderId;

        // Use Laravel's signed URL generation with forced root URL
        $originalUrl = URL::to('');
        URL::forceRootUrl($baseUrl);
        URL::forceScheme(parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https');

        try {
            $signedUrl = URL::temporarySignedRoute(
                'payments.invoice',
                $expiration,
                ['orderId' => $orderId]
            );
        } finally {
            URL::forceRootUrl($originalUrl);
        }

        return $signedUrl;
    }
}
