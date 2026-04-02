<?php

namespace App\Http\Controllers\Api\V6;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceOrder;
use App\Models\SubscriptionPlan;
use App\Services\V6\MarketplaceService;
use App\Traits\HandlesFileStorage;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    use HandlesFileStorage;

    protected $service;

    public function __construct(MarketplaceService $service)
    {
        $this->service = $service;
    }

    // ==========================================
    // PUBLIC MARKETPLACE (Discovery)
    // ==========================================

    /**
     * Get all active listings (Public)
     */
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'type', 'search']);
        $filters['exclude_user_id'] = auth()->id();
        
        $listings = $this->service->getListings($filters);

        return response()->json([
            'status' => 'success',
            'data' => $listings
        ]);
    }

    /**
     * Get single listing details (Public)
     */
    public function show($id)
    {
        $listing = MarketplaceListing::active()->with('user:id,name,profile_image')->findOrFail($id);
        $this->service->recordView($listing->id);

        return response()->json([
            'status' => 'success',
            'data' => $listing
        ]);
    }

    /**
     * Get trending listings from Redis (Public)
     */
    public function trending()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->service->getTrending(10)
        ]);
    }

    /**
     * Get marketplace plans (Public)
     */
    public function getPlans()
    {
        $plans = SubscriptionPlan::active()->where('s_type', 6)->orderBy('price')->get();
        return response()->json(['status' => 'success', 'data' => $plans]);
    }

    // ==========================================
    // BUYER INTERACTIONS (Authenticated)
    // ==========================================

    /**
     * Purchase soft copy listing (Cashfree)
     */
    public function purchase($id)
    {
        $listing = MarketplaceListing::active()->where('type', 'soft')->findOrFail($id);
        
        try {
            $checkoutUrl = $this->service->initiatePayment(auth()->user(), $listing);
            return response()->json([
                'status' => 'success',
                'checkout_url' => $checkoutUrl
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Contact seller for hard copy (Real Chat)
     */
    public function contactSeller($id)
    {
        $listing = MarketplaceListing::active()->where('type', 'hard')->findOrFail($id);
        
        try {
            $conversationId = $this->service->initiateChat(auth()->user(), $listing);
            return response()->json([
                'status' => 'success',
                'message' => 'Conversation initiated.',
                'conversation_id' => $conversationId
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Secure Download (Redis)
     */
    public function download(Request $request, $id)
    {
        $token = $request->query('token');
        $order = $this->service->validateDownloadToken($token);

        if (!$order || $order->listing_id != $id || $order->user_id !== auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Invalid or expired access.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'download_path' => $this->getFileUrl($order->listing->file_path)
        ]);
    }

    // ==========================================
    // SELLER MANAGEMENT (Authenticated)
    // ==========================================

    /**
     * List my own listings
     */
    public function myListings()
    {
        $listings = auth()->user()->marketplaceListings()->latest()->paginate(15);
        return response()->json(['status' => 'success', 'data' => $listings]);
    }

    /**
     * Create new listing
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string',
            'type' => 'required|in:soft,hard',
            'file_path' => 'required_if:type,soft|file|mimes:pdf,doc,docx,zip,rar,txt|max:51200',
            'thumbnail' => 'nullable|string',
            'images' => 'required|array|min:4|max:6',
            'images.*' => 'required|string',
        ]);

        try {
            if ($request->hasFile('file_path')) {
                $validated['file_path'] = $this->uploadFile(
                    $request->file('file_path'),
                    auth()->id(),
                    'soft_copy',
                    'marketplace'
                );
            }

            $listing = $this->service->createListing(auth()->user(), $validated);
            return response()->json(['status' => 'success', 'data' => $listing], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Update listing
     */
    public function update(Request $request, $id)
    {
        $listing = auth()->user()->marketplaceListings()->findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,sold,inactive',
            'file_path' => 'sometimes|file|mimes:pdf,doc,docx,zip,rar,txt|max:51200',
            'images' => 'sometimes|array|min:4|max:6',
            'images.*' => 'sometimes|string',
        ]);

        if ($request->hasFile('file_path')) {
            if ($listing->file_path) {
                $this->deleteFile($listing->file_path);
            }
            $validated['file_path'] = $this->uploadFile(
                $request->file('file_path'),
                auth()->id(),
                'soft_copy',
                'marketplace'
            );
        }

        $listing->update($validated);
        
        return response()->json(['status' => 'success', 'data' => $listing]);
    }

    /**
     * Remove listing
     */
    public function destroy($id)
    {
        $listing = auth()->user()->marketplaceListings()->findOrFail($id);
        
        if ($listing->file_path) {
            $this->deleteFile($listing->file_path);
        }
        
        $listing->delete();

        return response()->json(['status' => 'success', 'message' => 'Listing removed.']);
    }
}
