<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends BaseApiController
{
    /**
     * Get authenticated user's leads only.
     * Returns leads owned by, assigned to, or created by the auth user.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Lead::query()
            ->forAuthUser($user->id)
            ->with(['user:id,name,email', 'leadOwner:id,name,email', 'assignedTo:id,name,email'])
            ->orderByDesc('created_at');

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

        if (!$this->userCanAccessLead($lead, $user)) {
            return $this->notFound('Lead not found or access denied.');
        }

        $lead->load(['user:id,name,email', 'leadOwner:id,name,email', 'assignedTo:id,name,email']);

        return $this->success('Lead retrieved successfully.', $lead->toArray());
    }

    /**
     * Check if the user can access the lead (owner, assigned, or creator).
     */
    private function userCanAccessLead(Lead $lead, User $user): bool
    {
        return $lead->user_id === $user->id
            || $lead->lead_owner_id === $user->id
            || $lead->assigned_to === $user->id;
    }
}
