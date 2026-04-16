<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseNoteRequest;
use App\Http\Resources\NotePurchaseResource;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use App\Models\NoteCategory;
use App\Models\NotePurchase;
use App\Models\NoteType;
use App\Services\NotePurchaseService;
use App\Services\SubscriptionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class NoteController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NotePurchaseService $notePurchaseService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * List notes with filters (category, type, pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'notes:index:'.sha1($this->normalizeForCache($request->query()));
        $payload = Cache::remember($cacheKey, $this->readCacheTtlSeconds(), function () use ($request) {
            $query = Note::where('is_active', true)
                ->with(['noteType', 'noteCategory']);

            if ($request->filled('category_id')) {
                $query->where('note_category_id', $request->integer('category_id'));
            }

            if ($request->filled('note_type_id')) {
                $query->where('note_type_id', $request->integer('note_type_id'));
            }

            if ($request->filled('search')) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('is_paid')) {
                $query->where('is_paid', $request->boolean('is_paid'));
            }

            $query->orderBy('created_at', 'desc');

            $perPage = min((int) $request->get('per_page', 15), 50);
            $notes = $query->paginate($perPage);

            $data = $notes->through(function (Note $note) use ($request) {
                return (new NoteResource($note))->toArray($request);
            });

            return [
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
            ];
        });

        return $this->success('Notes retrieved successfully.', $payload);
    }

    /**
     * Get a single note by ID.
     */
    public function show(Note $note): JsonResponse
    {
        if (!$note->is_active) {
            return $this->notFound('Note not found or inactive.');
        }

        $cacheKey = 'notes:show:'.$note->id;
        $payload = Cache::remember($cacheKey, $this->readCacheTtlSeconds(), function () use ($note, $cacheKey) {
            $freshNote = Note::query()
                ->whereKey($note->id)
                ->where('is_active', true)
                ->with(['noteType', 'noteCategory'])
                ->first();

            if (!$freshNote) {
                Cache::forget($cacheKey);

                return null;
            }

            return [
                'note' => (new NoteResource($freshNote))->toArray(request()),
            ];
        });

        if ($payload === null) {
            return $this->notFound('Note not found or inactive.');
        }

        return $this->success('Note retrieved successfully.', $payload);
    }

    /**
     * Check if user has access to a note (purchased or subscription).
     */
    public function checkAccess(Note $note): JsonResponse
    {
        if (!$note->is_active) {
            return $this->notFound('Note not found or inactive.');
        }

        $user = Auth::user();
        $isPurchased = $note->isPurchasedBy($user->id);
        $hasSubscriptionAccess = $this->subscriptionService->hasActiveSubscription($user, 2);
        $canAccess = $isPurchased || $hasSubscriptionAccess || !$note->is_paid;

        return $this->success('Access check result.', [
            'note_id' => $note->id,
            'can_access' => $canAccess,
            'is_purchased' => $isPurchased,
            'has_subscription_access' => $hasSubscriptionAccess,
            'message' => $canAccess ? 'You have access to this note.' : 'Purchase or subscribe to access this note.',
        ]);
    }

    /**
     * Initiate purchase of a note.
     */
    public function purchase(PurchaseNoteRequest $request): JsonResponse
    {
        $user = Auth::user();
        $note = $request->getNote();

        if (!$note) {
            return $this->notFound('Note not found or inactive.');
        }

        if (!$note->is_paid || $note->price <= 0) {
            return $this->success('This note is free. No payment required.', [
                'note' => new NoteResource($note),
                'payment_required' => false,
            ]);
        }

        $result = $this->notePurchaseService->getOrCreateNotePurchaseCheckoutUrl($user, $note, 'api');

        if (!$result['success']) {
            return $this->error(
                $result['message'] ?? 'Failed to create note purchase payment.',
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($result['already_paid'] ?? false) {
            return $this->success('You have already purchased this note.', [
                'order_id' => $result['order_id'] ?? null,
                'status' => 'already_paid',
                'note' => new NoteResource($note),
            ]);
        }

        return $this->success('Note purchase payment created successfully.', [
            'payment' => [
                'order_id' => $result['order_id'],
                'amount' => $result['amount'],
                'currency' => $result['currency'],
                'status' => 'pending',
            ],
            'checkout_url' => $result['checkout_url'],
            'payment_session_id' => $result['payment_session_id'] ?? null,
            'note' => new NoteResource($note),
        ]);
    }

    /**
     * Download note file (requires access).
     */
    public function download(Note $note): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if (!$note->is_active) {
            return $this->notFound('Note not found or inactive.');
        }

        $user = Auth::user();
        $isPurchased = $note->isPurchasedBy($user->id);
        $hasSubscriptionAccess = $this->subscriptionService->hasActiveSubscription($user, 2);
        $canAccess = $isPurchased || $hasSubscriptionAccess || !$note->is_paid;

        if (!$canAccess) {
            return $this->forbidden('You must purchase this note or have an active subscription to download it.');
        }

        $disk = config('filesystems.upload_disk', 'public');
        if (!$note->file_path || !Storage::disk($disk)->exists($note->file_path)) {
            return $this->notFound('Note file not found.');
        }

        $purchase = $note->getUserPurchase($user->id);
        if ($purchase) {
            $purchase->increment('download_count');
        }

        $note->increment('download_count');

        $filename = basename($note->file_path) ?: 'note-' . $note->id . '.pdf';

        return Storage::disk($disk)->download($note->file_path, $filename);
    }

    /**
     * Get user's purchased notes.
     */
    public function myPurchases(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = NotePurchase::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['note.noteType', 'note.noteCategory']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $purchases = $query->orderBy('purchased_at', 'desc')->paginate($perPage);

        return $this->success('Your note purchases retrieved successfully.', [
            'data' => NotePurchaseResource::collection($purchases->items()),
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
                'from' => $purchases->firstItem(),
                'to' => $purchases->lastItem(),
            ],
            'links' => [
                'first' => $purchases->url(1),
                'last' => $purchases->url($purchases->lastPage()),
                'prev' => $purchases->previousPageUrl(),
                'next' => $purchases->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get note categories (for filters).
     */
    public function categories(): JsonResponse
    {
        $categories = Cache::remember('notes:categories', $this->readCacheTtlSeconds(), static function () {
            return NoteCategory::active()
                ->ordered()
                ->get(['id', 'name', 'slug', 'description']);
        });

        return $this->success('Note categories retrieved successfully.', [
            'categories' => $categories,
        ]);
    }

    /**
     * Get note types (for filters).
     */
    public function types(): JsonResponse
    {
        $types = Cache::remember('notes:types', $this->readCacheTtlSeconds(), static function () {
            return NoteType::active()
                ->ordered()
                ->get(['id', 'code', 'name', 'description']);
        });

        return $this->success('Note types retrieved successfully.', [
            'types' => $types,
        ]);
    }

    private function readCacheTtlSeconds(): int
    {
        return max(30, (int) config('cache.notes_api_ttl_seconds', 120));
    }

    private function normalizeForCache(array $input): string
    {
        ksort($input);

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = $this->normalizeForCache($value);
            }
        }

        return json_encode($input, JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
