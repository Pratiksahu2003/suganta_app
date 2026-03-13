<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Services\ActivityNotificationService;
use App\Traits\HandlesFileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class SupportTicketController extends BaseApiController
{
    use HandlesFileStorage;

    protected ActivityNotificationService $notificationService;

    public function __construct(ActivityNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
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
        try {
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
                'attachment' => [
                    'nullable',
                    'file',
                    'max:10240',
                    'mimes:jpg,jpeg,png,pdf,doc,docx,txt,zip'
                ],
                'user_notes' => ['nullable', 'string', 'max:1000'],
            ]);

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->uploadFile(
                    $request->file('attachment'),
                    $user->id,
                    'ticket',
                    'support-ticket'
                );
            }

            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'priority' => $validated['priority'],
                'category' => $validated['category'],
                'attachment_path' => $attachmentPath,
                'user_notes' => $validated['user_notes'] ?? null,
                'status' => SupportTicket::STATUS_OPEN,
            ]);

            $ticket->load(['user', 'assignedAdmin']);

            $this->notificationService->supportTicketCreated($ticket);

            return $this->created($ticket, 'Support ticket created successfully.');
        } catch (Exception $e) {
            Log::error('Failed to create support ticket', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return $this->error('Failed to create support ticket. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
        try {
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
                'attachment' => [
                    'nullable',
                    'file',
                    'max:10240',
                    'mimes:jpg,jpeg,png,pdf,doc,docx,txt,zip'
                ],
                'user_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            ];

            if ($user->isAdmin()) {
                $rules = array_merge($rules, [
                    'status' => [
                        'sometimes',
                        'string',
                        Rule::in(array_keys(SupportTicket::getStatusOptions())),
                    ],
                    'admin_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
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

            $originalData = $supportTicket->only(['status', 'priority', 'assigned_to', 'category']);

            if ($request->hasFile('attachment')) {
                if ($supportTicket->attachment_path) {
                    $this->deleteFile($supportTicket->attachment_path);
                }
                $validated['attachment_path'] = $this->uploadFile(
                    $request->file('attachment'),
                    $user->id,
                    'ticket',
                    'support-ticket'
                );
            }

            $supportTicket->fill($validated);

            if (isset($validated['status']) && $validated['status'] === SupportTicket::STATUS_RESOLVED && !$supportTicket->resolved_at) {
                $supportTicket->resolved_at = now();
            }

            $supportTicket->save();

            $changes = array_diff_assoc(
                $supportTicket->only(['status', 'priority', 'assigned_to', 'category']),
                $originalData
            );

            if (!empty($changes)) {
                $this->notificationService->supportTicketUpdated($supportTicket, $changes);
            }

            return $this->success('Support ticket updated successfully.', $supportTicket->fresh(['user', 'assignedAdmin']));
        } catch (Exception $e) {
            Log::error('Failed to update support ticket', [
                'ticket_id' => $supportTicket->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return $this->error('Failed to update support ticket. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete (soft delete) a support ticket.
     */
    public function destroy(SupportTicket $supportTicket): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$supportTicket->canBeClosedBy($user)) {
                return $this->forbidden('You are not allowed to delete this ticket.');
            }

            $supportTicket->delete();

            return $this->noContent();
        } catch (Exception $e) {
            Log::error('Failed to delete support ticket', [
                'ticket_id' => $supportTicket->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return $this->error('Failed to delete support ticket. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reply to a support ticket.
     */
    public function reply(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$supportTicket->canBeRepliedToBy($user)) {
                return $this->forbidden('You are not allowed to reply to this ticket.');
            }

            $validated = $request->validate([
                'message' => ['required', 'string'],
                'attachment' => [
                    'nullable',
                    'file',
                    'max:10240',
                    'mimes:jpg,jpeg,png,pdf,doc,docx,txt,zip'
                ],
                'internal_notes' => ['nullable', 'string', 'max:1000'],
            ]);

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $this->uploadFile(
                    $request->file('attachment'),
                    $user->id,
                    'reply',
                    'support-ticket'
                );
            }

            $reply = SupportTicketReply::create([
                'support_ticket_id' => $supportTicket->id,
                'user_id' => $user->id,
                'message' => $validated['message'],
                'is_admin_reply' => $user->isAdmin(),
                'attachment_path' => $attachmentPath,
                'internal_notes' => $validated['internal_notes'] ?? null,
            ]);

            $supportTicket->update([
                'status' => $user->isAdmin() 
                    ? SupportTicket::STATUS_WAITING_FOR_USER 
                    : SupportTicket::STATUS_IN_PROGRESS
            ]);

            $reply->load('user');

            $this->notificationService->supportTicketReplied($supportTicket, $reply);

            return $this->created($reply, 'Reply added successfully.');
        } catch (Exception $e) {
            Log::error('Failed to reply to support ticket', [
                'ticket_id' => $supportTicket->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return $this->error('Failed to add reply. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Download attachment from support ticket.
     */
    public function downloadAttachment(SupportTicket $supportTicket): mixed
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$supportTicket->canDownloadAttachmentsBy($user)) {
                return $this->forbidden('You are not allowed to download attachments from this ticket.');
            }

            if (!$supportTicket->attachment_path) {
                return $this->notFound('No attachment found for this ticket.');
            }

            $disk = config('filesystems.upload_disk', 'public');
            if (!Storage::disk($disk)->exists($supportTicket->attachment_path)) {
                return $this->notFound('Attachment file not found.');
            }

            return Storage::disk($disk)->download(
                $supportTicket->attachment_path,
                $supportTicket->getAttachmentFilename() ?? 'attachment'
            );
        } catch (Exception $e) {
            Log::error('Failed to download support ticket attachment', [
                'ticket_id' => $supportTicket->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return $this->error('Failed to download attachment. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Download attachment from support ticket reply.
     */
    public function downloadReplyAttachment(SupportTicket $supportTicket, SupportTicketReply $reply): mixed
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$supportTicket->canDownloadAttachmentsBy($user)) {
                return $this->forbidden('You are not allowed to download attachments from this ticket.');
            }

            if ($reply->support_ticket_id !== $supportTicket->id) {
                return $this->forbidden('Reply does not belong to this ticket.');
            }

            if (!$reply->attachment_path) {
                return $this->notFound('No attachment found for this reply.');
            }

            $disk = config('filesystems.upload_disk', 'public');
            if (!Storage::disk($disk)->exists($reply->attachment_path)) {
                return $this->notFound('Attachment file not found.');
            }

            return Storage::disk($disk)->download(
                $reply->attachment_path,
                $reply->getAttachmentFilename() ?? 'attachment'
            );
        } catch (Exception $e) {
            Log::error('Failed to download reply attachment', [
                'ticket_id' => $supportTicket->id ?? null,
                'reply_id' => $reply->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return $this->error('Failed to download attachment. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

