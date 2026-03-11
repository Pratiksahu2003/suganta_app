<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LeadController extends BaseApiController
{
    /**
     * Create a new lead (auth user ID stored in user_id).
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'type' => ['nullable', 'string', Rule::in(['student', 'parent', 'institute', 'teacher'])],
            'source' => ['nullable', 'string', Rule::in(['website', 'social_media', 'referral', 'advertisement', 'direct'])],
            'subject_interest' => ['nullable', 'string', 'max:255'],
            'grade_level' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'string', Rule::in(['new', 'contacted', 'qualified', 'converted', 'closed'])],
            'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'lead_owner_id' => ['required', 'integer', 'exists:users,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
        ]);

        $lead = Lead::create(array_merge($validated, [
            'user_id' => $user->id,
        ]));

        $lead->load(['user:id,name,email', 'leadOwner:id,name,email', 'assignedTo:id,name,email']);

        return $this->created($lead->toArray(), 'Lead created successfully.');
    }

    /**
     * Get authenticated user's leads only.
     * Returns leads owned by, assigned to, or created by the auth user.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Lead::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('lead_owner_id', $user->id)
                    ->orWhere('assigned_to', $user->id);
            })
            ->with([
                'user:id,name,email',
                'leadOwner:id,name,email',
                'assignedTo:id,name,email'
            ])
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->byStatus($request->string('status'));
        }

        if ($request->filled('search')) {
            $query->search($request->string('search'));
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->byDateRange(
                $request->string('start_date') ?: null,
                $request->string('end_date') ?: null
            );
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $leads = $query->paginate($perPage);

        return $this->success('Leads retrieved successfully.', [
            'data' => $leads->items(),
            'meta' => [
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
                'from' => $leads->firstItem(),
                'to' => $leads->lastItem(),
            ],
            'links' => [
                'first' => $leads->url(1),
                'last' => $leads->url($leads->lastPage()),
                'prev' => $leads->previousPageUrl(),
                'next' => $leads->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a single lead belonging to the authenticated user.
     */
    public function show(Lead $lead): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $lead->load(['user:id,name,email', 'leadOwner:id,name,email', 'assignedTo:id,name,email']);

        return $this->success('Lead retrieved successfully.', $lead->toArray());
    }

    /**
     * Check if the user can access the lead (owner, assigned, or creator).
     */
    private function userCanAccessLead(Lead $lead, User $user): bool
    {
        return $lead->user_id == $user->id
            || $lead->lead_owner_id == $user->id
            || $lead->assigned_to == $user->id;
    }
}
