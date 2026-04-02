<?php

namespace App\Http\Controllers\Api\V6;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceOrder;
use App\Models\SubscriptionPlan;
use App\Services\V6\MarketplaceService;
use App\Traits\HandlesFileStorage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    use HandlesFileStorage, ApiResponse;

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
        return $this->success('Listings retrieved successfully', $listings);
    }

    /**
     * Get single listing details (Public)
     */
    public function show($id)
    {
        $listing = MarketplaceListing::active()->with('user:id,name,profile_image')->findOrFail($id);
        $this->service->recordView($listing->id);
        return $this->success('Listing details retrieved', $listing);
    }

    /**
     * Get trending listings from Redis (Public)
     */
    public function trending()
    {
        return $this->success('Trending listings retrieved', $this->service->getTrending(10));
    }

    /**
     * Get marketplace plans (Public)
     */
    public function getPlans()
    {
        $plans = SubscriptionPlan::active()->where('s_type', 6)->orderBy('price')->get();
        return $this->success('Marketplace plans retrieved', $plans);
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
            return $this->success('Checkout URL generated', ['checkout_url' => $checkoutUrl]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
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
            return $this->success('Conversation initiated', ['conversation_id' => $conversationId]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
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
            return $this->forbidden('Invalid or expired access.');
        }

        return $this->success('Download link generated', [
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
        return $this->paginated($listings, 'My listings retrieved');
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
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072',
            'images' => 'required|array|min:4|max:6',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:3072',
        ]);

        try {
            if ($request->hasFile('images')) {
                $validated['images'] = $this->uploadMultipleFiles(
                    $request->file('images'),
                    auth()->id(),
                    'image',
                    'marketplace'
                );
            }

            if ($request->hasFile('file_path')) {
                $validated['file_path'] = $this->uploadFile(
                    $request->file('file_path'),
                    auth()->id(),
                    'soft_copy',
                    'marketplace'
                );
            }

            if ($request->hasFile('thumbnail')) {
                $validated['thumbnail'] = $this->uploadFile(
                    $request->file('thumbnail'),
                    auth()->id(),
                    'thumbnail',
                    'marketplace'
                );
            }

            $listing = $this->service->createListing(auth()->user(), $validated);
            return $this->created($listing, 'Listing created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
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
            'thumbnail' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:3072',
            'images' => 'sometimes|array|min:4|max:6',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:3072',
        ]);

        if ($request->hasFile('images')) {
            // Delete old images if they exist
            if (!empty($listing->images)) {
                $this->deleteMultipleFiles($listing->images);
            }
            $validated['images'] = $this->uploadMultipleFiles(
                $request->file('images'),
                auth()->id(),
                'image',
                'marketplace'
            );
        }

        if ($request->hasFile('thumbnail')) {
            if ($listing->thumbnail) {
                $this->deleteFile($listing->thumbnail);
            }
            $validated['thumbnail'] = $this->uploadFile(
                $request->file('thumbnail'),
                auth()->id(),
                'thumbnail',
                'marketplace'
            );
        }

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
        return $this->success('Listing updated successfully', $listing);
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

        if ($listing->thumbnail) {
            $this->deleteFile($listing->thumbnail);
        }

        if (!empty($listing->images)) {
            $this->deleteMultipleFiles($listing->images);
        }
        
        $listing->delete();
        return $this->success('Listing removed');
    }
}
