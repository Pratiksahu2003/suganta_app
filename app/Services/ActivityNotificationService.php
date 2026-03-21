<?php

namespace App\Services;

use App\Models\User;
use App\Models\TeacherSession;
use App\Models\SupportTicket;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Institute;
use App\Models\TeacherProfile;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ActivityNotificationService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function sessionCreated(TeacherSession $session): void
    {
        try {
            $teacher = $session->teacher;
            $institute = $session->institute ?? null;

            if (!$teacher) {
                Log::warning('Session created notification skipped: teacher not found', ['session_id' => $session->id]);
                return;
            }

            $sessionData = $this->buildSessionData($session);
            $scheduledAt = $session->date && $session->time 
                ? Carbon::parse($session->date . ' ' . $session->time)->format('M d, Y g:i A')
                : 'TBD';

            $this->notificationService->createUserNotification(
                $teacher->id,
                'New Session Created',
                "Your session '{$session->title}' has been created successfully for {$scheduledAt}",
                'session',
                array_merge($sessionData, [
                    'resource_type' => 'session',
                    'resource_id' => $session->id,
                    'action' => 'view'
                ]),
                null,
                'normal'
            );

            if ($institute && $institute->user_id !== $teacher->id) {
                $this->notificationService->createUserNotification(
                    $institute->user_id,
                    'New Session Created',
                    "A new session '{$session->title}' has been created by {$teacher->name} in your institute",
                    'session',
                    array_merge($sessionData, [
                        'teacher_name' => $teacher->name,
                        'resource_type' => 'session',
                        'resource_id' => $session->id,
                        'action' => 'view'
                    ]),
                    null,
                    'normal'
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send session created notification', [
                'session_id' => $session->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sessionUpdated(TeacherSession $session, array $changes = []): void
    {
        try {
            if (empty($changes)) {
                return;
            }

            $teacher = $session->teacher;
            if (!$teacher) {
                Log::warning('Session updated notification skipped: teacher not found', ['session_id' => $session->id]);
                return;
            }

            $changeText = $this->buildChangeMessage($changes);
            $sessionData = array_merge($this->buildSessionData($session), [
                'changes' => $changes,
                'resource_type' => 'session',
                'resource_id' => $session->id,
                'action' => 'view'
            ]);

            $this->notificationService->createUserNotification(
                $teacher->id,
                'Session Updated',
                "Session '{$session->title}' has been updated: {$changeText}",
                'session',
                $sessionData,
                null,
                'normal'
            );

            if ($session->students) {
                foreach ($session->students as $student) {
                    $this->notificationService->createUserNotification(
                        $student->user_id,
                        'Session Updated',
                        "Session '{$session->title}' has been updated: {$changeText}",
                        'session',
                        $sessionData,
                        null,
                        'normal'
                    );
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to send session updated notification', [
                'session_id' => $session->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sessionCancelled(TeacherSession $session, ?string $reason = null): void
    {
        try {
            $teacher = $session->teacher;
            if (!$teacher) {
                Log::warning('Session cancelled notification skipped: teacher not found', ['session_id' => $session->id]);
                return;
            }

            $reasonText = $reason ? " Reason: {$reason}" : '';
            $sessionData = array_merge($this->buildSessionData($session), [
                'reason' => $reason,
                'resource_type' => 'session',
                'resource_id' => $session->id,
                'action' => 'view'
            ]);

            $this->notificationService->createUserNotification(
                $teacher->id,
                'Session Cancelled',
                "Session '{$session->title}' has been cancelled.{$reasonText}",
                'session',
                $sessionData,
                null,
                'high'
            );

            if ($session->students) {
                foreach ($session->students as $student) {
                    $this->notificationService->createUserNotification(
                        $student->user_id,
                        'Session Cancelled',
                        "Session '{$session->title}' with {$teacher->name} has been cancelled.{$reasonText}",
                        'session',
                        array_merge($sessionData, ['teacher_name' => $teacher->name]),
                        null,
                        'high'
                    );
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to send session cancelled notification', [
                'session_id' => $session->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function sessionReminder(TeacherSession $session, string $timeUntil = '1 hour'): void
    {
        try {
            $teacher = $session->teacher;
            if (!$teacher) {
                Log::warning('Session reminder notification skipped: teacher not found', ['session_id' => $session->id]);
                return;
            }

            $sessionData = array_merge($this->buildSessionData($session), [
                'time_until' => $timeUntil,
                'resource_type' => 'session',
                'resource_id' => $session->id,
                'action' => 'view'
            ]);

            $this->notificationService->createUserNotification(
                $teacher->id,
                'Session Reminder',
                "Reminder: You have a session '{$session->title}' in {$timeUntil}",
                'session',
                $sessionData,
                null,
                'normal'
            );

            if ($session->students) {
                foreach ($session->students as $student) {
                    $this->notificationService->createUserNotification(
                        $student->user_id,
                        'Session Reminder',
                        "Reminder: You have a session '{$session->title}' with {$teacher->name} in {$timeUntil}",
                        'session',
                        array_merge($sessionData, ['teacher_name' => $teacher->name]),
                        null,
                        'normal'
                    );
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to send session reminder notification', [
                'session_id' => $session->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function supportTicketCreated(SupportTicket $ticket): void
    {
        try {
            $user = $ticket->user;
            if (!$user) {
                Log::warning('Support ticket created notification skipped: user not found', ['ticket_id' => $ticket->id]);
                return;
            }

            $ticketData = array_merge($this->buildTicketData($ticket), [
                'resource_type' => 'support_ticket',
                'resource_id' => $ticket->id,
                'action' => 'view'
            ]);

            $this->notificationService->createUserNotification(
                $user->id,
                'Support Ticket Created',
                "Your support ticket '{$ticket->subject}' has been created successfully. Ticket ID: #{$ticket->id}",
                'support',
                $ticketData,
                null,
                'normal'
            );

            $this->notificationService->createRoleNotification(
                ['admin'],
                'New Support Ticket',
                "New support ticket '{$ticket->subject}' created by {$user->name}. Priority: {$ticket->priority}",
                'support',
                array_merge($ticketData, ['user_name' => $user->name]),
                null,
                $this->mapPriority($ticket->priority)
            );
        } catch (Exception $e) {
            Log::error('Failed to send support ticket created notification', [
                'ticket_id' => $ticket->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function userVerified(User $user): void
    {
        try {
            $this->notificationService->createUserNotification(
                $user->id,
                'Account Verified',
                "Congratulations! Your account has been verified successfully. You now have full access to all platform features.",
                'account',
                [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role' => $user->role,
                    'verified_at' => now(),
                    'resource_type' => 'profile',
                    'resource_id' => $user->id,
                    'action' => 'view'
                ],
                null,
                'normal'
            );
        } catch (Exception $e) {
            Log::error('Failed to send user verified notification', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function userRejected(User $user, ?string $reason = null): void
    {
        try {
            $message = $reason 
                ? "Your account verification was not approved. Reason: {$reason}" 
                : "Your account verification was not approved. Please contact support for more information.";

            $this->notificationService->createUserNotification(
                $user->id,
                'Account Verification Update',
                $message,
                'account',
                [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role' => $user->role,
                    'reason' => $reason,
                    'rejected_at' => now(),
                    'resource_type' => 'support_ticket',
                    'action' => 'create'
                ],
                null,
                'high'
            );
        } catch (Exception $e) {
            Log::error('Failed to send user rejected notification', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function supportTicketUpdated(SupportTicket $ticket, array $changes = []): void
    {
        try {
            if (empty($changes)) {
                return;
            }

            $user = $ticket->user;
            if (!$user) {
                Log::warning('Support ticket updated notification skipped: user not found', ['ticket_id' => $ticket->id]);
                return;
            }

            $changeText = $this->buildTicketChangeMessage($changes);
            $ticketData = array_merge($this->buildTicketData($ticket), [
                'changes' => $changes,
                'resource_type' => 'support_ticket',
                'resource_id' => $ticket->id,
                'action' => 'view'
            ]);

            $this->notificationService->createUserNotification(
                $user->id,
                'Support Ticket Updated',
                "Your support ticket '{$ticket->subject}' has been updated: {$changeText}",
                'support',
                $ticketData,
                null,
                'normal'
            );

            if (isset($changes['assigned_to']) && $changes['assigned_to']) {
                $assignee = User::find($changes['assigned_to']);
                if ($assignee) {
                    $this->notificationService->createUserNotification(
                        $assignee->id,
                        'Support Ticket Assigned',
                        "Support ticket '{$ticket->subject}' has been assigned to you",
                        'support',
                        $ticketData,
                        null,
                        'normal'
                    );
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to send support ticket updated notification', [
                'ticket_id' => $ticket->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function supportTicketReplied(SupportTicket $ticket, $reply): void
    {
        try {
            $user = $ticket->user;
            $replier = $reply->user ?? null;

            if (!$user || !$replier) {
                Log::warning('Support ticket replied notification skipped: user or replier not found', [
                    'ticket_id' => $ticket->id,
                    'reply_id' => $reply->id ?? null
                ]);
                return;
            }

            $ticketData = array_merge($this->buildTicketData($ticket), [
                'replier_name' => $replier->name,
                'reply_id' => $reply->id,
                'resource_type' => 'support_ticket',
                'resource_id' => $ticket->id,
                'action' => 'view'
            ]);

            if ($user->id !== $replier->id) {
                $this->notificationService->createUserNotification(
                    $user->id,
                    'Support Ticket Reply',
                    "You have received a reply on your support ticket '{$ticket->subject}' from {$replier->name}",
                    'support',
                    $ticketData,
                    null,
                    'normal'
                );
            }

            if ($ticket->assigned_to && $ticket->assigned_to !== $replier->id && $ticket->assigned_to !== $user->id) {
                $this->notificationService->createUserNotification(
                    $ticket->assigned_to,
                    'Support Ticket Reply',
                    "A reply has been added to support ticket '{$ticket->subject}' by {$replier->name}",
                    'support',
                    $ticketData,
                    null,
                    'normal'
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send support ticket replied notification', [
                'ticket_id' => $ticket->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function paymentSuccessful(Payment $payment, float $largePaymentThreshold = 1000.0): void
    {
        try {
            $user = $payment->user;
            if (!$user) {
                Log::warning('Payment successful notification skipped: user not found', ['payment_id' => $payment->id]);
                return;
            }

            $paymentData = array_merge($this->buildPaymentData($payment), [
                'resource_type' => 'payment',
                'resource_id' => $payment->id,
                'action' => 'view'
            ]);

            $this->notificationService->createUserNotification(
                $user->id,
                'Payment Successful',
                "Your payment of {$payment->currency} {$payment->amount} has been processed successfully. Transaction ID: {$payment->transaction_id}",
                'payment',
                $paymentData,
                null,
                'normal'
            );

            if ($payment->amount > $largePaymentThreshold) {
                $this->notificationService->createRoleNotification(
                    ['admin'],
                    'Large Payment Received',
                    "Large payment of {$payment->currency} {$payment->amount} received from {$user->name}",
                    'payment',
                    array_merge($paymentData, ['user_name' => $user->name]),
                    null,
                    'normal'
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send payment successful notification', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function paymentFailed(Payment $payment, ?string $reason = null): void
    {
        try {
            $user = $payment->user;
            if (!$user) {
                Log::warning('Payment failed notification skipped: user not found', ['payment_id' => $payment->id]);
                return;
            }

            $reasonText = $reason ? " Reason: {$reason}" : '';
            $paymentData = array_merge($this->buildPaymentData($payment), [
                'reason' => $reason,
                'resource_type' => 'payment',
                'resource_id' => $payment->id,
                'action' => 'view'
            ]);

            $this->notificationService->createUserNotification(
                $user->id,
                'Payment Failed',
                "Your payment of {$payment->currency} {$payment->amount} has failed.{$reasonText}",
                'payment',
                $paymentData,
                null,
                'high'
            );
        } catch (Exception $e) {
            Log::error('Failed to send payment failed notification', [
                'payment_id' => $payment->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function newReview(Review $review): void
    {
        try {
            $reviewer = $review->user;
            $teacher = $review->teacher;

            if (!$reviewer || !$teacher) {
                Log::warning('New review notification skipped: reviewer or teacher not found', ['review_id' => $review->id]);
                return;
            }

            $reviewData = array_merge($this->buildReviewData($review), [
                'resource_type' => 'review',
                'resource_id' => $review->id,
                'action' => 'view'
            ]);

            $this->notificationService->createUserNotification(
                $teacher->user_id,
                'New Review Received',
                "You have received a new {$review->rating}-star review from {$reviewer->name}",
                'review',
                $reviewData,
                null,
                'normal'
            );

            if ($teacher->institute && $teacher->institute->user_id !== $teacher->user_id) {
                $this->notificationService->createUserNotification(
                    $teacher->institute->user_id,
                    'New Teacher Review',
                    "{$teacher->user->name} has received a new {$review->rating}-star review from {$reviewer->name}",
                    'review',
                    array_merge($reviewData, ['teacher_name' => $teacher->user->name]),
                    null,
                    'normal'
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send new review notification', [
                'review_id' => $review->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }


    public function teacherJoinedInstitute(TeacherProfile $teacher, Institute $institute): void
    {
        try {
            $teacherUser = $teacher->user;
            if (!$teacherUser) {
                Log::warning('Teacher joined institute notification skipped: teacher user not found', [
                    'teacher_id' => $teacher->id,
                    'institute_id' => $institute->id
                ]);
                return;
            }

            $instituteData = [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacherUser->name,
                'institute_id' => $institute->id,
                'institute_name' => $institute->name,
                'joined_at' => now(),
                'resource_type' => 'institute',
                'resource_id' => $institute->id,
                'action' => 'view'
            ];

            $this->notificationService->createUserNotification(
                $institute->user_id,
                'New Teacher Joined',
                "{$teacherUser->name} has joined your institute as a teacher",
                'institute',
                $instituteData,
                null,
                'normal'
            );

            $this->notificationService->createRoleNotification(
                ['admin'],
                'Teacher Joined Institute',
                "{$teacherUser->name} has joined {$institute->name} as a teacher",
                'institute',
                $instituteData,
                null,
                'normal'
            );
        } catch (Exception $e) {
            Log::error('Failed to send teacher joined institute notification', [
                'teacher_id' => $teacher->id ?? null,
                'institute_id' => $institute->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function studentEnrolled(StudentProfile $student, Institute $institute): void
    {
        try {
            $studentUser = $student->user;
            if (!$studentUser) {
                Log::warning('Student enrolled notification skipped: student user not found', [
                    'student_id' => $student->id,
                    'institute_id' => $institute->id
                ]);
                return;
            }

            $enrollmentData = [
                'student_id' => $student->id,
                'student_name' => $studentUser->name,
                'institute_id' => $institute->id,
                'institute_name' => $institute->name,
                'enrolled_at' => now(),
                'resource_type' => 'institute',
                'resource_id' => $institute->id,
                'action' => 'view'
            ];

            $this->notificationService->createUserNotification(
                $institute->user_id,
                'New Student Enrolled',
                "{$studentUser->name} has enrolled in your institute",
                'institute',
                $enrollmentData,
                null,
                'normal'
            );

            $this->notificationService->createUserNotification(
                $student->user_id,
                'Enrollment Successful',
                "You have successfully enrolled in {$institute->name}",
                'institute',
                $enrollmentData,
                null,
                'normal'
            );
        } catch (Exception $e) {
            Log::error('Failed to send student enrolled notification', [
                'student_id' => $student->id ?? null,
                'institute_id' => $institute->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function systemMaintenance(string $scheduledAt, string $duration, ?string $description = null): void
    {
        try {
            $formattedDate = Carbon::parse($scheduledAt)->format('M d, Y g:i A');
            $message = "System maintenance is scheduled for {$formattedDate} for {$duration}.";
            
            if ($description) {
                $message .= " {$description}";
            }

            $this->notificationService->createRoleNotification(
                ['admin', 'teacher', 'student', 'institute'],
                'System Maintenance Scheduled',
                $message,
                'system',
                [
                    'scheduled_at' => $scheduledAt,
                    'duration' => $duration,
                    'description' => $description,
                    'announced_at' => now(),
                    'resource_type' => 'system',
                    'action' => 'info'
                ],
                null,
                'high'
            );
        } catch (Exception $e) {
            Log::error('Failed to send system maintenance notification', [
                'scheduled_at' => $scheduledAt ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function newFeature(string $featureName, string $description, ?string $actionUrl = null): void
    {
        try {
            $this->notificationService->createRoleNotification(
                ['admin', 'teacher', 'student', 'institute'],
                'New Feature Available',
                "New feature '{$featureName}' is now available! {$description}",
                'feature',
                [
                    'feature_name' => $featureName,
                    'description' => $description,
                    'announced_at' => now(),
                    'resource_type' => 'feature',
                    'action' => 'info'
                ],
                $actionUrl,
                'normal'
            );
        } catch (Exception $e) {
            Log::error('Failed to send new feature notification', [
                'feature_name' => $featureName ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function securityAlert(User $user, string $alertType, string $description): void
    {
        try {
            $this->notificationService->createUserNotification(
                $user->id,
                'Security Alert',
                "Security alert: {$description}",
                'security',
                [
                    'user_id' => $user->id,
                    'alert_type' => $alertType,
                    'description' => $description,
                    'alerted_at' => now(),
                    'resource_type' => 'profile',
                    'resource_id' => $user->id,
                    'action' => 'security'
                ],
                null,
                'high'
            );
        } catch (Exception $e) {
            Log::error('Failed to send security alert notification', [
                'user_id' => $user->id ?? null,
                'alert_type' => $alertType ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function dailySummary(User $user, string $summary): void
    {
        try {
            $this->notificationService->createUserNotification(
                $user->id,
                'Daily Summary',
                $summary,
                'summary',
                [
                    'user_id' => $user->id,
                    'date' => now()->format('Y-m-d'),
                    'summary' => $summary,
                    'generated_at' => now(),
                    'resource_type' => 'dashboard',
                    'action' => 'view'
                ],
                null,
                'low'
            );
        } catch (Exception $e) {
            Log::error('Failed to send daily summary notification', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function weeklyReport(User $user, string $report): void
    {
        try {
            $this->notificationService->createUserNotification(
                $user->id,
                'Weekly Report',
                $report,
                'report',
                [
                    'user_id' => $user->id,
                    'week' => now()->format('Y-W'),
                    'report' => $report,
                    'generated_at' => now(),
                    'resource_type' => 'report',
                    'action' => 'view'
                ],
                null,
                'normal'
            );
        } catch (Exception $e) {
            Log::error('Failed to send weekly report notification', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function buildSessionData(TeacherSession $session): array
    {
        return [
            'session_id' => $session->id,
            'session_title' => $session->title,
            'session_type' => $session->type,
            'scheduled_date' => $session->date,
            'scheduled_time' => $session->time,
            'duration' => $session->duration,
            'price' => $session->price,
            'status' => $session->status
        ];
    }

    protected function buildTicketData(SupportTicket $ticket): array
    {
        return [
            'ticket_id' => $ticket->id,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'category' => $ticket->category ?? null
        ];
    }

    protected function buildPaymentData(Payment $payment): array
    {
        return [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'transaction_id' => $payment->transaction_id,
            'status' => $payment->status,
            'payment_method' => $payment->payment_method ?? null
        ];
    }

    protected function buildReviewData(Review $review): array
    {
        return [
            'review_id' => $review->id,
            'reviewer_name' => $review->user->name ?? 'Unknown',
            'rating' => $review->rating,
            'comment' => $this->truncateText($review->comment ?? '', 100),
            'created_at' => $review->created_at
        ];
    }

    protected function buildChangeMessage(array $changes): string
    {
        $changeMessages = [];
        foreach ($changes as $field => $value) {
            $changeMessages[] = match ($field) {
                'title' => "title changed to '{$value}'",
                'date' => "date changed to " . Carbon::parse($value)->format('M d, Y'),
                'time' => "time changed to " . Carbon::parse($value)->format('g:i A'),
                'status' => "status changed to {$value}",
                'price' => "price changed to {$value}",
                default => "{$field} updated"
            };
        }

        return implode(', ', $changeMessages);
    }

    protected function buildTicketChangeMessage(array $changes): string
    {
        $changeMessages = [];
        foreach ($changes as $field => $value) {
            $changeMessages[] = match ($field) {
                'status' => "status changed to {$value}",
                'priority' => "priority changed to {$value}",
                'assigned_to' => "assigned to " . (User::find($value)?->name ?? 'staff'),
                'category' => "category changed to {$value}",
                default => "{$field} updated"
            };
        }

        return implode(', ', $changeMessages);
    }

    protected function truncateText(string $text, int $length = 100): string
    {
        return strlen($text) > $length 
            ? substr($text, 0, $length) . '...' 
            : $text;
    }

    protected function mapPriority(string $priority): string
    {
        return match (strtolower($priority)) {
            'high', 'urgent', 'critical' => 'high',
            'low' => 'low',
            default => 'normal'
        };
    }
} 