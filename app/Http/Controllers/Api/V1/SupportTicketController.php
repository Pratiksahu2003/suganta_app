<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class SupportTicketController extends BaseApiController
{
    /**
     * Get dropdown option values for support tickets.
     */
    public function options(): JsonResponse
    {
        return $this->success('Support ticket options retrieved successfully.', [
            'priorities' => SupportTicket::getPriorityOptions(),
            'statuses' => SupportTicket::getStatusOptions(),
            'categories' => SupportTicket::getCategoryOptions(),
        ]);
    }

    /**
     * List support tickets for the authenticated user (or all for admins).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $query = SupportTicket::query()->with(['user', 'assignedAdmin']);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        } else {
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        $tickets = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Support tickets retrieved successfully.', $tickets);
    }

    /**
     * Create a new support ticket for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'priority' => [
                'required',
                'string',
                Rule::in([
                    SupportTicket::PRIORITY_LOW,
                    SupportTicket::PRIORITY_MEDIUM,
                    SupportTicket::PRIORITY_HIGH,
                    SupportTicket::PRIORITY_URGENT,
                ]),
            ],
            'category' => [
                'required',
                'string',
                Rule::in(array_keys(SupportTicket::getCategoryOptions())),
            ],
            'attachment_path' => ['nullable', 'string', 'max:255'],
            'user_notes' => ['nullable', 'string'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'attachment_path' => $validated['attachment_path'] ?? null,
            'user_notes' => $validated['user_notes'] ?? null,
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        return $this->created($ticket, 'Support ticket created successfully.');
    }

    /**
     * Show a specific support ticket.
     */
    public function show(SupportTicket $supportTicket): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$supportTicket->canBeAccessedBy($user)) {
            return $this->forbidden('You are not allowed to access this ticket.');
        }

        $supportTicket->load(['user', 'assignedAdmin', 'replies']);

        return $this->success('Support ticket retrieved successfully.', $supportTicket);
    }

    /**
     * Update an existing support ticket.
     */
    public function update(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$supportTicket->canBeAccessedBy($user)) {
            return $this->forbidden('You are not allowed to update this ticket.');
        }

        $rules = [
            'subject' => ['sometimes', 'string', 'max:255'],
            'message' => ['sometimes', 'string'],
            'priority' => [
                'sometimes',
                'string',
                Rule::in([
                    SupportTicket::PRIORITY_LOW,
                    SupportTicket::PRIORITY_MEDIUM,
                    SupportTicket::PRIORITY_HIGH,
                    SupportTicket::PRIORITY_URGENT,
                ]),
            ],
            'category' => [
                'sometimes',
                'string',
                Rule::in(array_keys(SupportTicket::getCategoryOptions())),
            ],
            'attachment_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'user_notes' => ['sometimes', 'nullable', 'string'],
        ];

        if ($user->isAdmin()) {
            $rules = array_merge($rules, [
                'status' => [
                    'sometimes',
                    'string',
                    Rule::in(array_keys(SupportTicket::getStatusOptions())),
                ],
                'admin_notes' => ['sometimes', 'nullable', 'string'],
                'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
                'assigned_admin_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
                'resolved_at' => ['sometimes', 'nullable', 'date'],
            ]);
        }

        $validated = $request->validate($rules);

        if (!$user->isAdmin()) {
            unset(
                $validated['status'],
                $validated['admin_notes'],
                $validated['assigned_to'],
                $validated['assigned_admin_id'],
                $validated['resolved_at']
            );
        }

        $supportTicket->fill($validated);

        if (isset($validated['status']) && $validated['status'] === SupportTicket::STATUS_RESOLVED && !$supportTicket->resolved_at) {
            $supportTicket->resolved_at = now();
        }

        $supportTicket->save();

        return $this->success('Support ticket updated successfully.', $supportTicket->fresh(['user', 'assignedAdmin']));
    }

    /**
     * Delete (soft delete) a support ticket.
     */
    public function destroy(SupportTicket $supportTicket): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$supportTicket->canBeClosedBy($user)) {
            return $this->forbidden('You are not allowed to delete this ticket.');
        }

        $supportTicket->delete();

        return $this->noContent();
    }
}

