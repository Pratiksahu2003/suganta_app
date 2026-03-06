<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Profile;
use App\Models\ProfileInstituteInfo;
use App\Models\ProfileStudentInfo;
use App\Models\ProfileTeachingInfo;
use App\Services\PasswordNotificationService;
use App\Services\UserActivityNotificationService;
use App\Traits\HandlesFileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfileController extends BaseApiController
{
    use HandlesFileStorage;
    public function __construct(
        protected ?UserActivityNotificationService $userActivityService = null,
        protected PasswordNotificationService $passwordNotificationService
    ) {
    }

    /**
     * Get the authenticated user's full profile.
     * GET /api/v1/profile
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (!$profile) {
                $profile = Profile::create(['user_id' => $user->id]);
            }

            $profile->load(['instituteInfo', 'studentInfo', 'teachingInfo', 'professionalInfo', 'socialLinks']);
            $profile->updateCompletionPercentage();

            $data = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'profile' => $profile->toArray(),
                'profile_image_url' => $profile->profile_image ? $this->getFileUrl($profile->profile_image) : null,
                'completion_percentage' => $profile->profile_completion_percentage,
            ];

            return $this->success('Profile retrieved successfully.', $data);
        } catch (\Exception $e) {
            Log::error('Profile fetch error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverError('Unable to retrieve profile.', $e);
        }
    }

    /**
     * Update basic profile information.
     * PUT/PATCH /api/v1/profile
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'display_name' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:1000',
                'date_of_birth' => 'nullable|date',
                'gender_id' => 'nullable|integer|in:1,2,3,4',
                'nationality' => 'nullable|string|max:255',
                'phone_primary' => 'nullable|string|max:20',
                'phone_secondary' => 'nullable|string|max:20',
                'whatsapp' => 'nullable|string|max:20',
                'website' => 'nullable|url|max:255',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'email' => ['required', 'email', 'unique:users,email,' . $request->user()->id],
            ]);

            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            $oldProfileValues = $profile->only([
                'first_name', 'last_name', 'display_name', 'bio',
                'phone_primary', 'phone_secondary', 'whatsapp', 'website',
            ]);

            if ($user->email !== $request->email) {
                $oldEmail = $user->email;
                $user->email = $request->email;
                $user->email_verified_at = null;
                $user->save();
                if ($this->userActivityService) {
                    $this->userActivityService->emailChanged($user, $oldEmail, $request->email);
                }
            }

            $profile->update($request->only([
                'first_name', 'last_name', 'display_name', 'bio', 'date_of_birth',
                'gender_id', 'nationality', 'phone_primary', 'phone_secondary',
                'whatsapp', 'website', 'emergency_contact_name', 'emergency_contact_phone',
            ]));

            $newProfileValues = $profile->fresh()->only([
                'first_name', 'last_name', 'display_name', 'bio',
                'phone_primary', 'phone_secondary', 'whatsapp', 'website',
            ]);
            $changes = $this->detectProfileChanges($oldProfileValues, $newProfileValues);
            if (!empty($changes) && $this->userActivityService) {
                $this->userActivityService->profileUpdated($user, $changes);
            }

            $profile->updateCompletionPercentage();

            return $this->success('Profile information updated successfully.', [
                'profile' => $profile->fresh()->toArray(),
                'completion_percentage' => $profile->profile_completion_percentage,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage());
            return $this->serverError('Unable to update profile.');
        }
    }

    /**
     * Update location information.
     * PUT/PATCH /api/v1/profile/location
     */
    public function updateLocation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'address_line_1' => 'nullable|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'area' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'pincode' => 'nullable|string|max:20',
                'country_id' => 'nullable|integer|in:1,2,3,4,5,6,7,8,9,10',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ]);

            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            $oldValues = $profile->only([
                'address_line_1', 'address_line_2', 'area', 'city', 'state', 'pincode', 'country_id',
            ]);
            $profile->update($request->only([
                'address_line_1', 'address_line_2', 'area', 'city', 'state',
                'pincode', 'country_id', 'latitude', 'longitude',
            ]));
            $newValues = $profile->fresh()->only([
                'address_line_1', 'address_line_2', 'area', 'city', 'state', 'pincode', 'country_id',
            ]);
            $changes = $this->detectProfileChanges($oldValues, $newValues);
            if (!empty($changes)) {
                $this->sendNotification($user, $changes, 'Location Updated', 'Your location has been updated.');
            }

            $profile->updateCompletionPercentage();

            return $this->success('Location information updated successfully.', [
                'profile' => $profile->fresh()->toArray(),
                'completion_percentage' => $profile->profile_completion_percentage,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Location update error: ' . $e->getMessage());
            return $this->serverError('Unable to update location.');
        }
    }

    /**
     * Update social media links.
     * PUT/PATCH /api/v1/profile/social
     */
    public function updateSocial(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'facebook_url' => 'nullable|url|max:255',
                'twitter_url' => 'nullable|url|max:255',
                'instagram_url' => 'nullable|url|max:255',
                'linkedin_url' => 'nullable|url|max:255',
                'youtube_url' => 'nullable|url|max:255',
                'tiktok_url' => 'nullable|url|max:255',
                'telegram_username' => 'nullable|string|max:255',
                'discord_username' => 'nullable|string|max:255',
                'github_url' => 'nullable|url|max:255',
                'portfolio_url' => 'nullable|url|max:255',
                'blog_url' => 'nullable|url|max:255',
                'website' => 'nullable|url|max:255',
                'whatsapp' => 'nullable|string|max:20',
            ]);

            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            $oldValues = $profile->only([
                'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url',
                'youtube_url', 'tiktok_url', 'telegram_username', 'discord_username',
                'github_url', 'portfolio_url', 'blog_url', 'website', 'whatsapp',
            ]);
            $profile->update($request->only([
                'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url',
                'youtube_url', 'tiktok_url', 'telegram_username', 'discord_username',
                'github_url', 'portfolio_url', 'blog_url', 'website', 'whatsapp',
            ]));
            $newValues = $profile->fresh()->only([
                'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url',
                'youtube_url', 'tiktok_url', 'telegram_username', 'discord_username',
                'github_url', 'portfolio_url', 'blog_url', 'website', 'whatsapp',
            ]);
            $changes = $this->detectProfileChanges($oldValues, $newValues);
            if (!empty($changes)) {
                $this->sendNotification($user, $changes, 'Social Media Updated', 'Social links updated.');
            }

            $profile->updateCompletionPercentage();

            return $this->success('Social media links updated successfully.', [
                'profile' => $profile->fresh()->toArray(),
                'completion_percentage' => $profile->profile_completion_percentage,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Social update error: ' . $e->getMessage());
            return $this->serverError('Unable to update social links.');
        }
    }

    /**
     * Update teaching information.
     * PUT/PATCH /api/v1/profile/teaching
     */
    public function updateTeaching(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'highest_qualification' => 'nullable|string|max:255',
                'institution_name' => 'nullable|string|max:255',
                'field_of_study' => 'nullable|string|max:255',
                'graduation_year' => 'nullable|integer|min:1950|max:' . (date('Y') + 5),
                'teaching_experience_years' => 'nullable|integer|min:0|max:50',
                'hourly_rate_id' => 'nullable|integer|in:1,2,3,4,5,6,7,8,9,10',
                'monthly_rate_id' => 'nullable|integer|in:1,2,3,4,5,6,7,8,9,10',
                'travel_radius_km_id' => 'nullable|integer|in:0,1,2,3,4,5,6,7,8,9,10,15,20,25,30,40,50,75,100',
                'teaching_mode_id' => 'nullable|integer|in:1,2,3',
                'availability_status_id' => 'nullable|integer|in:1,2,3',
                'teaching_philosophy' => 'nullable|string|max:2000',
                'subjects_taught' => 'nullable|array',
                'subjects_taught.*' => 'integer|exists:subjects,id',
            ]);

            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            $profile->update($request->only([
                'highest_qualification', 'institution_name', 'field_of_study', 'graduation_year',
            ]));

            $teachingInfo = $profile->teachingInfo ?: ProfileTeachingInfo::create(['profile_id' => $profile->id]);
            $teachingInfo->update($request->only([
                'teaching_experience_years', 'hourly_rate_id', 'monthly_rate_id', 'travel_radius_km_id',
                'teaching_mode_id', 'availability_status_id', 'teaching_philosophy', 'subjects_taught',
            ]));

            $profile->updateCompletionPercentage();
            $profile->clearCompletionCache();
            $this->sendNotification($user, ['teaching_info' => 'updated'], 'Teaching Info Updated', 'Teaching info updated.');

            return $this->success('Teaching information updated successfully.', [
                'profile' => $profile->fresh()->toArray(),
                'teaching_info' => $teachingInfo->fresh()->toArray(),
                'completion_percentage' => $profile->profile_completion_percentage,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Teaching update error: ' . $e->getMessage());
            return $this->serverError('Unable to update teaching information.');
        }
    }

    /**
     * Update institute information.
     * PUT/PATCH /api/v1/profile/institute
     */
    public function updateInstitute(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'institute_name' => 'required|string|max:255',
                'institute_type_id' => 'required|integer|in:1,2,3,4,5',
                'institute_category_id' => 'nullable|integer|in:1,2,3',
                'affiliation_number' => 'nullable|string|max:255',
                'registration_number' => 'nullable|string|max:255',
                'establishment_year_id' => 'nullable|integer|in:1,2,3,4,5,6,7,8,9',
                'principal_name' => 'nullable|string|max:255',
                'principal_phone' => 'nullable|string|max:20',
                'principal_email' => 'nullable|email|max:255',
                'total_students_id' => 'nullable|integer|in:1,2,3,4,5,6,7,8',
                'total_teachers_id' => 'nullable|integer|in:1,2,3,4,5,6,7,8',
                'total_branches' => 'nullable|integer|min:1',
                'institute_description' => 'nullable|string|max:2000',
            ]);

            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            $instituteInfo = $profile->instituteInfo ?: ProfileInstituteInfo::create(['profile_id' => $profile->id]);
            $instituteInfo->update($request->only([
                'institute_name', 'institute_type_id', 'institute_category_id', 'affiliation_number',
                'registration_number', 'establishment_year_id', 'principal_name', 'principal_phone',
                'principal_email', 'total_students_id', 'total_teachers_id', 'total_branches',
                'institute_description',
            ]));

            $profile->updateCompletionPercentage();
            $this->sendNotification($user, ['institute_info' => 'updated'], 'Institute Info Updated', 'Institute info updated.');

            return $this->success('Institute information updated successfully.', [
                'institute_info' => $instituteInfo->fresh()->toArray(),
                'completion_percentage' => $profile->profile_completion_percentage,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Institute update error: ' . $e->getMessage());
            return $this->serverError('Unable to update institute information.');
        }
    }

    /**
     * Update student information.
     * PUT/PATCH /api/v1/profile/student
     */
    public function updateStudent(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_class_id' => 'nullable|integer|in:1,2,3,4,5,6,7,8,9,10,11,12,13,14',
                'current_school' => 'nullable|string|max:255',
                'board_id' => 'nullable|integer|in:1,2,3,4,5',
                'stream_id' => 'nullable|integer|in:1,2,3,4,5,6',
                'parent_name' => 'nullable|string|max:255',
                'parent_phone' => 'nullable|string|max:20',
                'parent_email' => 'nullable|email|max:255',
                'budget_min' => 'nullable|numeric|min:0',
                'budget_max' => 'nullable|numeric|min:0',
                'learning_challenges' => 'nullable|string|max:1000',
            ]);

            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            $studentInfo = $profile->studentInfo ?: ProfileStudentInfo::create(['profile_id' => $profile->id]);
            $studentInfo->update($request->only([
                'current_class_id', 'current_school', 'board_id', 'stream_id',
                'parent_name', 'parent_phone', 'parent_email', 'budget_min', 'budget_max', 'learning_challenges',
            ]));

            $profile->updateCompletionPercentage();
            $this->sendNotification($user, ['student_info' => 'updated'], 'Student Info Updated', 'Student info updated.');

            return $this->success('Student information updated successfully.', [
                'student_info' => $studentInfo->fresh()->toArray(),
                'completion_percentage' => $profile->profile_completion_percentage,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Student update error: ' . $e->getMessage());
            return $this->serverError('Unable to update student information.');
        }
    }

    /**
     * Update profile avatar (image).
     * PUT/POST /api/v1/profile/avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            if ($profile->profile_image) {
                $this->deleteFile($profile->profile_image);
            }

            $path = $this->uploadFile(
                $request->file('avatar'),
                $user->id,
                'avatar',
                'profile'
            );

            $profile->update(['profile_image' => $path]);
            $this->sendNotification($user, ['avatar' => 'updated'], 'Profile Picture Updated', 'Avatar updated.');
            $profile->updateCompletionPercentage();

            return $this->success('Profile picture updated successfully.', [
                'profile_image' => $path,
                'profile_image_url' => $this->getFileUrl($path),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Avatar update error: ' . $e->getMessage());
            return $this->serverError('Unable to update profile picture.');
        }
    }

    /**
     * Update password.
     * PUT/PATCH /api/v1/profile/password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required|current_password',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = $request->user();
            $strength = $this->calculatePasswordStrength($request->password);
            if ($strength < 3) {
                return $this->validationError(['password' => ['Password is too weak. Choose a stronger password with letters, numbers, and symbols.']], 'Validation failed.');
            }

            $user->update(['password' => bcrypt($request->password)]);
            $this->passwordNotificationService->passwordUpdated($user, [
                'changed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->success('Password updated successfully.');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Password update error: ' . $e->getMessage());
            return $this->serverError('Unable to update password.');
        }
    }

    /**
     * Update preferences.
     * PUT/PATCH /api/v1/profile/preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'theme' => 'nullable|in:light,dark,auto',
                'language' => 'nullable|string|max:10',
                'notifications' => 'nullable|array',
                'privacy_settings' => 'nullable|array',
            ]);

            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            $preferences = $profile->preferences ?? [];
            $preferences = array_merge($preferences, $request->only(['theme', 'language', 'notifications', 'privacy_settings']));
            $profile->update(['preferences' => $preferences]);

            $this->sendNotification($user, $request->only(['theme', 'language', 'notifications']), 'Preferences Updated', 'Preferences updated.');

            return $this->success('Preferences updated successfully.', [
                'preferences' => $profile->fresh()->preferences,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Preferences update error: ' . $e->getMessage());
            return $this->serverError('Unable to update preferences.');
        }
    }

    /**
     * Get profile completion data.
     * GET /api/v1/profile/completion
     */
    public function completion(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            $cacheKey = "profile_completion_data_{$profile->id}";
            $cachedData = Cache::get($cacheKey);

            if ($cachedData && $cachedData['timestamp'] > now()->subMinutes(2)) {
                return $this->success('Completion data retrieved.', [
                    'percentage' => $cachedData['percentage'],
                    'status' => $cachedData['status'],
                    'color' => $cachedData['color'],
                    'completion_summary' => $cachedData['completion_summary'],
                    'cached' => true,
                ]);
            }

            $profile->refresh();
            $user->refresh();
            $percentage = $profile->updateCompletionPercentage();
            $status = $profile->getCompletionStatusText();
            $color = $profile->getCompletionStatusColor();
            $completionSummary = $profile->getCompletionSummary();

            Cache::put($cacheKey, [
                'percentage' => $percentage,
                'status' => $status,
                'color' => $color,
                'completion_summary' => $completionSummary,
                'timestamp' => now(),
            ], 120);

            return $this->success('Completion data retrieved.', [
                'percentage' => $percentage,
                'status' => $status,
                'color' => $color,
                'completion_summary' => $completionSummary,
                'cached' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Profile completion error: ' . $e->getMessage());
            return $this->serverError('Unable to get profile completion data.');
        }
    }

    /**
     * Refresh profile data (force reload, clear caches).
     * POST /api/v1/profile/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile ?: Profile::create(['user_id' => $user->id]);

            $user->refresh();
            $profile->refresh();

            try {
                $profile->ensureRelationshipsExist();
                $profile->load(['instituteInfo', 'studentInfo', 'teachingInfo', 'professionalInfo', 'socialLinks']);
            } catch (\Exception $e) {
                // Continue without relationships
            }

            Cache::forget('user_profile_' . $user->id);
            Cache::forget('profile_completion_' . $user->id);
            Cache::forget('user_' . $user->id);
            Cache::forget('profile_' . $profile->id);
            Cache::forget("profile_completion_data_{$profile->id}");

            return $this->success('Profile data refreshed successfully.', [
                'profile_image_url' => $profile->profile_image ? $this->getFileUrl($profile->profile_image) : null,
                'profile' => $profile->fresh()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Profile refresh error: ' . $e->getMessage());
            return $this->serverError('Unable to refresh profile data.');
        }
    }

    /**
     * Clear profile-related caches.
     * POST /api/v1/profile/cache/clear
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            Cache::forget('user_profile_' . $user->id);
            Cache::forget('profile_completion_' . $user->id);
            Cache::forget('user_' . $user->id);
            if ($user->profile) {
                Cache::forget('profile_' . $user->profile->id);
                Cache::forget("profile_completion_data_{$user->profile->id}");
            }

            return $this->success('Profile caches cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Cache clear error: ' . $e->getMessage());
            return $this->serverError('Unable to clear caches.');
        }
    }

    /**
     * Delete user account (permanent).
     * DELETE /api/v1/profile
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'password' => 'required|current_password',
                'confirmation' => 'required|in:DELETE',
                'reason' => 'nullable|string|max:500',
            ]);

            $user = $request->user();

            if ($this->userActivityService && method_exists($this->userActivityService, 'accountDeleted')) {
                $this->userActivityService->accountDeleted($user, [
                    'deleted_at' => now(),
                    'reason' => $request->reason ?? 'User requested deletion',
                ]);
            }

            // Revoke all tokens before deleting user
            $user->tokens()->delete();
            $user->delete();

            return $this->success('Your account has been permanently deleted.', null, 200);
        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Validation failed.');
        } catch (\Exception $e) {
            Log::error('Account deletion error: ' . $e->getMessage());
            return $this->serverError('Unable to delete account.');
        }
    }

    private function detectProfileChanges(array $oldValues, array $newValues): array
    {
        $changes = [];
        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;
            if (is_array($newValue)) $newValue = json_encode($newValue);
            if (is_array($oldValue)) $oldValue = json_encode($oldValue);
            if ($oldValue !== $newValue) {
                $changes[$field] = $newValue;
            }
        }
        return $changes;
    }

    private function calculatePasswordStrength(string $password): int
    {
        $strength = 0;
        if (strlen($password) >= 8) $strength++;
        if (strlen($password) >= 12) $strength++;
        if (preg_match('/[a-z]/', $password)) $strength++;
        if (preg_match('/[A-Z]/', $password)) $strength++;
        if (preg_match('/[0-9]/', $password)) $strength++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $strength++;
        return $strength;
    }

    private function sendNotification($user, array $changes, ?string $title = null, ?string $message = null): void
    {
        if ($this->userActivityService) {
            $this->userActivityService->profileUpdated($user, $changes);
        } else {
            \App\Models\Notification::createNotification(
                $user->id,
                $title ?: 'Profile Updated',
                $message ?: 'Your profile has been updated successfully.',
                'profile',
                ['changes' => $changes],
                url('/dashboard/profile'),
                'normal'
            );
        }
    }
}
