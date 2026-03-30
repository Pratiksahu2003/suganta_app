<?php

namespace App\Services\Chatbot;

use App\Models\Chatbot\ChatbotConversation;
use App\Models\Chatbot\ChatbotIntent;
use App\Models\Chatbot\ChatbotLead;
use App\Models\Chatbot\ChatbotAnalytics;
use App\Models\Chatbot\ChatbotUser;
use Illuminate\Support\Facades\Log;

class LeadCaptureService
{
    /**
     * Automatically capture a lead if the matched intent is a lead-capture intent.
     */
    public function captureIfRelevant(
        ChatbotUser          $user,
        ChatbotConversation  $conversation,
        string               $matchedBy,
        ?int                 $intentId,
        string               $messageText,
    ): void {
        // Only capture on intent matches that are in the lead_capture_intents list
        if ($matchedBy !== 'intent' || ! $intentId) {
            return;
        }

        $intent = ChatbotIntent::find($intentId);
        if (! $intent) {
            return;
        }

        $captureIntents = config('chatbot.lead_capture_intents', []);
        if (! in_array($intent->name, $captureIntents, true)) {
            return;
        }

        // Prevent duplicate leads for same user + conversation
        $existingLead = ChatbotLead::where('chatbot_user_id', $user->id)
            ->where('conversation_id', $conversation->id)
            ->first();

        if ($existingLead) {
            // Update interest if new
            if ($existingLead->interest !== $intent->name) {
                $extraData = $existingLead->extra_data ?? [];
                $extraData['additional_interests'][] = $intent->name;
                $extraData['latest_message'] = $messageText;
                $existingLead->update(['extra_data' => $extraData]);
            }
            return;
        }

        try {
            ChatbotLead::create([
                'chatbot_user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone'           => $user->phone,
                'source'          => $conversation->platform,
                'interest'        => $intent->name,
                'extra_data'      => [
                    'original_message' => $messageText,
                    'platform_user_id' => $user->platform_user_id,
                ],
                'status'          => 'new',
            ]);

            ChatbotAnalytics::incrementToday('leads_captured', $conversation->platform);

            Log::info('Chatbot: Lead captured', [
                'user_id'     => $user->id,
                'intent'      => $intent->name,
                'platform'    => $conversation->platform,
            ]);

        } catch (\Exception $e) {
            Log::error('Chatbot: Failed to capture lead', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Manually capture a lead from admin interface.
     */
    public function captureManually(
        int    $chatbotUserId,
        ?int   $conversationId,
        string $interest,
        array  $extraData = [],
    ): ChatbotLead {
        $user = ChatbotUser::findOrFail($chatbotUserId);

        return ChatbotLead::create([
            'chatbot_user_id' => $user->id,
            'conversation_id' => $conversationId,
            'name'            => $user->name,
            'email'           => $user->email,
            'phone'           => $user->phone,
            'source'          => $user->platform,
            'interest'        => $interest,
            'extra_data'      => $extraData,
            'status'          => 'new',
        ]);
    }
}
