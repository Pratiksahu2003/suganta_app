<?php

namespace App\Services\V6;

use App\Models\MarketplaceListing;
use App\Models\MarketplaceOrder;
use App\Models\Payment;
use App\Models\Chat\ChatConversation;
use App\Models\Chat\ChatConversationParticipant;
use App\Models\Chat\ChatMessage;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CashfreeService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class MarketplaceService
{
    private const TRENDING_KEY = 'marketplace:trending_listings';
    private const USER_COUNT_KEY = 'marketplace:user:{id}:listings_count';
    private const CACHE_TAG = 'marketplace_listings';
    private const COMMISSION_PERCENT = 10; // 10% platform commission
    protected $notificationService;
    protected $cashfree;

    public function __construct(NotificationService $notificationService, CashfreeService $cashfree)
    {
        $this->notificationService = $notificationService;
        $this->cashfree = $cashfree;
    }

    /**
     * Get all active listings with caching
     */
    public function getListings(array $filters = [])
    {
        $cacheKey = 'marketplace:listings:' . md5(serialize($filters));

        return Cache::tags([self::CACHE_TAG])->remember($cacheKey, 3600, function () use ($filters) {
            $query = MarketplaceListing::active()->with([
                'user:id,name',
                'user.profile:id,user_id,profile_image'
            ]);

            if (!empty($filters['exclude_user_id'])) {
                $query->where('user_id', '!=', $filters['exclude_user_id']);
            }

            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['search'])) {
                $query->where('title', 'like', '%' . $filters['search'] . '%');
            }

            return $query->latest()->paginate(15);
        });
    }

    /**
     * Create a new listing with Redis limit check
     */
    public function createListing(User $user, array $data)
    {
        // 1. Check Subscription & Limits
        $subscription = $user->marketplaceSubscription()->with('plan')->first();
        if (!$subscription || !$subscription->isActive()) {
            throw new \Exception('No active marketplace subscription found.');
        }

        $maxListings = $subscription->plan->max_listings ?? 0;

        // 2. Redis-based fast limit check
        $countKey = str_replace('{id}', $user->id, self::USER_COUNT_KEY);
        $currentCount = Redis::get($countKey);

        if ($currentCount === null) {
            $currentCount = $user->getCurrentMarketplaceListingCount();
            Redis::setex($countKey, 86400, $currentCount);
        }

        if ($currentCount >= $maxListings) {
            throw new \Exception("Listing limit reached ({$maxListings}). Please upgrade your plan.");
        }

        // 3. Create Listing
        $listing = $user->marketplaceListings()->create($data);

        // 4. Update Redis & Invalidate Cache
        Redis::incr($countKey);
        Cache::tags([self::CACHE_TAG])->flush();

        return $listing;
    }

    /**
     * Record a view and update trending (Redis Sorted Set)
     */
    public function recordView(int $listingId)
    {
        // Increment global trending score
        Redis::zincrby(self::TRENDING_KEY, 1, $listingId);

        // Update DB periodically (optional sync)
        MarketplaceListing::where('id', $listingId)->increment('views_count');
    }

    /**
     * Get trending listings from Redis
     */
    public function getTrending(int $limit = 10)
    {
        $ids = Redis::zrevrange(self::TRENDING_KEY, 0, $limit - 1);

        if (empty($ids)) {
            return collect();
        }

        return MarketplaceListing::whereIn('id', $ids)
            ->active()
            ->with([
                'user:id,name',
                'user.profile:id,user_id,profile_image'
            ])
            ->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')')
            ->get();
    }

    /**
     * Generate temporary download token in Redis
     */
    public function generateDownloadToken(int $orderId)
    {
        $token = Str::random(40);
        $key = 'marketplace:download:' . $token;

        Redis::setex($key, 300, $orderId); // 5 minute TTL

        return $token;
    }

    /**
     * Validate download token and return order
     */
    public function validateDownloadToken(string $token)
    {
        $key = 'marketplace:download:' . $token;
        $orderId = Redis::get($key);

        if (!$orderId) {
            return null;
        }

        return MarketplaceOrder::with('listing')->find($orderId);
    }

    /**
     * Get user's successful purchases
     */
    public function getUserPurchases(User $user)
    {
        return MarketplaceOrder::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['listing', 'listing.user', 'listing.user.profile'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    /**
     * Start/Get chat conversation for hard copy listing (ai_mysql)
     */
    public function initiateChat(User $buyer, MarketplaceListing $listing)
    {
        $seller = $listing->user;

        return DB::connection('ai_mysql')->transaction(function () use ($buyer, $seller, $listing) {
            // 1. Check for existing private conversation
            $conversationId = ChatConversation::query()
                ->join('chat_conversation_participants as me', 'me.conversation_id', '=', 'chat_conversations.id')
                ->join('chat_conversation_participants as other', 'other.conversation_id', '=', 'chat_conversations.id')
                ->where('chat_conversations.type', 'private')
                ->whereNull('me.left_at')
                ->whereNull('other.left_at')
                ->where('me.user_id', $buyer->id)
                ->where('other.user_id', $seller->id)
                ->value('chat_conversations.id');

            if (!$conversationId) {
                // 2. Create new conversation
                $conversation = ChatConversation::create([
                    'type' => 'private',
                    'created_by' => $buyer->id,
                ]);

                ChatConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $buyer->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);

                ChatConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $seller->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);

                $conversationId = $conversation->id;
            }

            // 3. Send interest message
            ChatMessage::create([
                'conversation_id' => $conversationId,
                'sender_id' => $buyer->id,
                'message' => "I am interested in your listing: {$listing->title}. Is it still available?",
                'type' => 'text',
            ]);

            // 4. Send Push Notification to Seller
            $this->notificationService->createUserNotification(
                $seller->id,
                'New Marketplace Interest',
                "{$buyer->name} is interested in your listing: {$listing->title}",
                'marketplace_contact',
                [
                    'listing_id' => $listing->id,
                    'conversation_id' => $conversationId,
                    'buyer_name' => $buyer->name
                ],
                route('v6.marketplace.show', $listing->id),
                'high'
            );

            return $conversationId;
        });
    }

    /**
     * Initiate Cashfree payment for soft copy listing
     */
    public function initiatePayment(User $buyer, MarketplaceListing $listing)
    {
        // 1. Check for existing pending payment to avoid redundant API calls
        $existingPayment = Payment::where('user_id', $buyer->id)
            ->where('meta->type', 'marketplace')
            ->where('meta->listing_id', $listing->id)
            ->whereIn('status', ['created', 'pending'])
            ->latest()
            ->first();

        if ($existingPayment) {
            return $this->buildProxyCheckoutUrl($existingPayment->order_id);
        }

        $orderId = 'MP-' . strtoupper(Str::random(10));

        // 2. Create Payment record
        $payment = Payment::create([
            'order_id' => $orderId,
            'user_id' => $buyer->id,
            'currency' => 'INR',
            'amount' => $listing->price,
            'status' => 'pending',
            'meta' => [
                'type' => 'marketplace',
                'listing_id' => $listing->id,
                'description' => "Purchase: {$listing->title}"
            ]
        ]);

        // Build Cashfree payload
        $payload = $this->cashfree->buildOrderPayload(
            $orderId,
            (string) $buyer->id,
            $buyer->email ?? '',
            $buyer->phone ?? '',
            (float) $listing->price,
            'INR',
            $buyer->name
        );

        $response = $this->cashfree->createOrder($payload);

        $payment->update([
            'gateway_response' => $response,
            'status' => 'pending'
        ]);

        return $this->buildProxyCheckoutUrl($orderId);
    }

    /**
     * Get fresh checkout data for an existing marketplace payment.
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
                $this->processSuccessfulPayment($payment, $freshOrder);
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
            Log::error('Failed to get fresh checkout data for marketplace payment', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build the standard checkout proxy URL for this order.
     */
    private function buildProxyCheckoutUrl(string $orderId): string
    {
        $parsed = parse_url(rtrim(config('app.url', 'http://localhost'), '/'));
        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'localhost');

        if (!empty($parsed['port'])) {
            $baseUrl .= ':' . $parsed['port'];
        }

        return $baseUrl . '/api/v1/payment/checkout?order_id=' . $orderId;
    }

    /**
     * Process successful payment (called from PaymentController webhook)
     */
    public function processSuccessfulPayment(Payment $payment, array $paymentData)
    {
        $listingId = $payment->meta['listing_id'] ?? null;
        if (!$listingId)
            return;

        $listing = MarketplaceListing::with('user')->find($listingId);
        if (!$listing)
            return;

        DB::transaction(function () use ($payment, $listing) {
            // 1. Calculate amounts (Commission & Seller Payout)
            $totalAmount = (float) $payment->amount;
            $commissionAmount = ($totalAmount * self::COMMISSION_PERCENT) / 100;
            $sellerAmount = $totalAmount - $commissionAmount;

            // 2. Create MarketplaceOrder
            $order = MarketplaceOrder::create([
                'user_id' => $payment->user_id,
                'listing_id' => $listing->id,
                'amount' => $totalAmount,
                'commission_amount' => $commissionAmount,
                'seller_amount' => $sellerAmount,
                'payment_id' => $payment->id,
                'status' => 'completed'
            ]);

            // 3. Credit Seller's Wallet
            Wallet::getOrCreate($listing->user_id)->credit(
                $sellerAmount,
                'marketplace_sale',
                $order->id,
                MarketplaceOrder::class,
                "Sale of listing: {$listing->title}"
            );

            // 4. Update Trending Score
            $this->incrementTrending($listing->id);

            // 5. Notify Seller via Firebase
            $this->notificationService->createUserNotification(
                $listing->user_id,
                'Item Sold!',
                "Your listing '{$listing->title}' has been purchased for ₹{$listing->price}.",
                'marketplace_sale',
                [
                    'listing_id' => $listing->id,
                    'order_id' => $order->id,
                    'amount' => $listing->price
                ],
                route('v6.marketplace.my-listings'),
                'high'
            );
        });
    }
}
