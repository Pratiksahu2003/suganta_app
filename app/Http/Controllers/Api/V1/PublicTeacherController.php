<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FilterOptionsHelper;
use App\Helpers\ProfileOptionsHelper;
use App\Helpers\PublicProfileOptionsMapper;
use App\Models\Profile;
use App\Models\ProfileTeachingInfo;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicTeacherController extends BaseApiController
{
    private const EXCLUDED_USER_ID = 40;
    private const ONLINE_TEACHING_MODE_EXCLUDE = 2; // Offline Only
    private const RELATED_TEACHERS_CACHE_TTL = 300; // 5 min

    /**
     * Get filter options for teacher listing.
     */
    public function options(): JsonResponse
    {
        $optionKeys = [
            'gender', 'teaching_mode', 'availability_status', 'hourly_rate_range',
            'monthly_rate_range', 'teaching_experience_years', 'travel_radius_km', 'highest_qualification',
        ];
        $data = [
            'options' => FilterOptionsHelper::buildFromConfig($optionKeys),
            'subjects' => FilterOptionsHelper::getActiveSubjects(),
            'cities' => $this->getTeacherCities(),
        ];
        return $this->success('Teacher filter options retrieved successfully.', $data);
    }

    /**
     * Get public list of teachers (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->buildBaseQuery();

        $this->applyFilters($query, $request);

        $sortBy = $request->query('sort', 'created_at');
        $sortOrder = $request->query('order', 'desc');
        $this->applyUserSorting($query, $sortBy, $sortOrder);

        $perPage = min(max(1, (int) $request->query('per_page', 12)), 50);
        $teachersPaginator = $query->paginate($perPage)->withQueryString();

        $teacherIds = $teachersPaginator->pluck('id')->all();
        $teachers = collect($teachersPaginator->items());
        $usedFallback = false;

        if ($teachers->isEmpty()) {
            $onlineTeachers = $this->getOnlineTeachersExcluding($teacherIds, $perPage);
            $teachers = $teachers->merge($onlineTeachers)->unique('id')->values();
            $usedFallback = true;
        }

        $items = $teachers->map(fn (User $user) => $this->formatListItem($user));

        $pagination = $usedFallback
            ? FilterOptionsHelper::fallbackPaginationMeta($request, $perPage, $teachers->count())
            : FilterOptionsHelper::paginationMeta($teachersPaginator);

        return $this->success('Teachers retrieved successfully.', [
            'teachers' => $items,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Get single teacher profile by User ID.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['profile', 'profile.teachingInfo', 'profile.socialLinks', 'portfolio'])
            ->where('role', 'teacher')
            ->whereNotNull('email_verified_at')
            ->where('id', '!=', self::EXCLUDED_USER_ID)
            ->whereIn('registration_fee_status', ['paid', 'not_required'])
            ->where('is_active', true)
            ->where('id', $id)
            ->first();

        if (!$user || !$user->profile) {
            return $this->notFound('Teacher not found.');
        }

        $profile = $user->profile;
        $teachingInfo = $profile->teachingInfo;

        $subjects = $this->getSubjectsForProfile($profile, $teachingInfo);

        $relatedTeachers = Cache::remember(
            'teacher_show_related_' . $id,
            self::RELATED_TEACHERS_CACHE_TTL,
            fn () => $this->getRelatedTeachers($profile)->map(fn (User $u) => $this->formatListItem($u))->all()
        );

        return $this->success('Teacher profile retrieved successfully.', [
            ...$this->formatShowItem($user, $profile, $teachingInfo, $subjects),
            'related_teachers' => $relatedTeachers,
        ]);
    }

    private function buildBaseQuery()
    {
        return User::with(['profile', 'profile.teachingInfo'])
            ->where('role', 'teacher')
            ->whereNotNull('email_verified_at')
            ->where('users.id', '!=', self::EXCLUDED_USER_ID)
            ->whereIn('registration_fee_status', ['paid', 'not_required']);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('location')) {
            $location = $request->get('location');
            $query->whereHas('profile', function ($q) use ($location) {
                $q->where(function ($sub) use ($location) {
                    $sub->where('city', 'like', '%' . $location . '%')
                        ->orWhere('area', 'like', '%' . $location . '%');
                });
            });
        }

        if ($request->filled('city') && !$request->filled('location')) {
            $city = $request->get('city');
            $query->whereHas('profile', fn ($q) => $q->where('city', 'like', '%' . $city . '%'));
        }

        if ($request->filled('area') && !$request->filled('location')) {
            $area = $request->get('area');
            $query->whereHas('profile', fn ($q) => $q->where('area', 'like', '%' . $area . '%'));
        }

        if ($request->filled('pincode')) {
            $pincode = $request->get('pincode');
            $query->whereHas('profile', fn ($q) => $q->where('pincode', $pincode));
        }

        $subjectId = $request->get('subject_id') ?? $request->get('subject');
        if ($subjectId) {
            $query->whereHas('profile', function ($q) use ($subjectId) {
                $q->whereHas('teachingInfo', function ($tq) use ($subjectId) {
                    $tq->whereJsonContains('subjects_taught', (string) $subjectId);
                });
            });
        }

        if ($request->filled('hourly_rate_range')) {
            $rateRange = $request->get('hourly_rate_range');
            $query->whereHas('profile.teachingInfo', fn ($q) => $q->where('hourly_rate_id', $rateRange));
        }

        if ($request->filled('monthly_rate_range')) {
            $rateRange = $request->get('monthly_rate_range');
            $query->whereHas('profile.teachingInfo', fn ($q) => $q->where('monthly_rate_id', $rateRange));
        }

        if ($request->filled('experience')) {
            $experience = $request->get('experience');
            $query->whereExists(function ($q) use ($experience) {
                $q->select(DB::raw(1))
                    ->from('profiles')
                    ->join('profile_teaching_info', 'profiles.id', '=', 'profile_teaching_info.profile_id')
                    ->whereColumn('profiles.user_id', 'users.id')
                    ->where('profile_teaching_info.teaching_experience_years', $experience);
            });
        }

        if ($request->filled('teaching_mode')) {
            $teachingMode = $request->get('teaching_mode');
            $query->whereExists(function ($q) use ($teachingMode) {
                $q->select(DB::raw(1))
                    ->from('profiles')
                    ->join('profile_teaching_info', 'profiles.id', '=', 'profile_teaching_info.profile_id')
                    ->whereColumn('profiles.user_id', 'users.id')
                    ->where('profile_teaching_info.teaching_mode_id', $teachingMode);
            });
        }

        if ($request->filled('availability')) {
            $availability = $request->get('availability');
            $availabilityId = is_numeric($availability)
                ? (int) $availability
                : ProfileOptionsHelper::getValue('availability_status', ucfirst($availability));

            if ($availabilityId !== null) {
                $query->whereHas('profile.teachingInfo', fn ($q) => $q->where('availability_status_id', $availabilityId));
            }
        }

        if ($request->filled('verified')) {
            $verified = $request->get('verified') === 'true' || $request->boolean('verified');
            $query->whereHas('profile.teachingInfo', fn ($q) => $q->where('verified', $verified));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', '%' . $search . '%')
                    ->orWhereHas('profile', fn ($pq) => $pq->where('display_name', 'like', '%' . $search . '%'));
            });
        }
    }

    private function applyUserSorting($query, string $sortBy, string $sortOrder): void
    {
        $order = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';

        switch ($sortBy) {
            case 'price_low':
            case 'price_high':
                $query->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
                    ->leftJoin('profile_teaching_info', 'profile_teaching_info.profile_id', '=', 'profiles.id')
                    ->select('users.*')
                    ->orderBy('profile_teaching_info.hourly_rate', $sortBy === 'price_low' ? 'asc' : 'desc');
                break;
            case 'rating':
                $query->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
                    ->leftJoin('profile_teaching_info', 'profile_teaching_info.profile_id', '=', 'profiles.id')
                    ->select('users.*')
                    ->orderBy('profile_teaching_info.rating', $order)
                    ->orderBy('profile_teaching_info.total_reviews', 'desc');
                break;
            case 'name':
                $query->orderBy('users.name', $order);
                break;
            case 'recent':
            case 'created_at':
            default:
                $query->orderBy('users.created_at', 'desc');
                break;
        }
    }

    private function getOnlineTeachersExcluding(array $excludeIds, int $limit = 10)
    {
        if (empty($excludeIds)) {
            $excludeIds = [0];
        }

        return User::with(['profile', 'profile.teachingInfo'])
            ->where('role', 'teacher')
            ->whereNotNull('email_verified_at')
            ->where('users.id', '!=', self::EXCLUDED_USER_ID)
            ->whereIn('registration_fee_status', ['paid', 'not_required'])
            ->whereNotIn('users.id', $excludeIds)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('profiles')
                    ->join('profile_teaching_info', 'profiles.id', '=', 'profile_teaching_info.profile_id')
                    ->whereColumn('profiles.user_id', 'users.id')
                    ->where('profile_teaching_info.teaching_mode_id', '!=', self::ONLINE_TEACHING_MODE_EXCLUDE);
            })
            ->limit($limit)
            ->get();
    }

    private function getRelatedTeachers(Profile $profile, int $limit = 6)
    {
        $subjectIds = $profile->teachingInfo?->subjects_taught ?? $profile->subjects_taught ?? [];
        if (is_string($subjectIds)) {
            $subjectIds = json_decode($subjectIds, true) ?? [];
        }
        $city = $profile->city;

        $query = User::with(['profile', 'profile.teachingInfo'])
            ->where('role', 'teacher')
            ->whereNotNull('email_verified_at')
            ->where('users.id', '!=', self::EXCLUDED_USER_ID)
            ->where('users.id', '!=', $profile->user_id)
            ->whereIn('registration_fee_status', ['paid', 'not_required'])
            ->where(function ($q) use ($subjectIds, $city) {
            if (!empty($subjectIds)) {
                $q->whereHas('profile.teachingInfo', function ($tq) use ($subjectIds) {
                    $tq->where(function ($sub) use ($subjectIds) {
                        foreach ((array) $subjectIds as $sid) {
                            $sub->orWhereJsonContains('subjects_taught', (string) $sid);
                        }
                    });
                });
            }
            if ($city) {
                $q->when(!empty($subjectIds), fn ($q) => $q->orWhereHas('profile', fn ($pq) => $pq->where('city', 'like', '%' . $city . '%')))
                    ->when(empty($subjectIds), fn ($q) => $q->whereHas('profile', fn ($pq) => $pq->where('city', 'like', '%' . $city . '%')));
            }
        });

        return $query->limit($limit)->get();
    }

    private function getTeacherCities(int $limit = 50): array
    {
        return Cache::remember('teacher_cities_profile', 3600, function () use ($limit) {
            return User::where('role', 'teacher')
                ->whereNotNull('email_verified_at')
                ->whereIn('registration_fee_status', ['paid', 'not_required'])
                ->join('profiles', 'users.id', '=', 'profiles.user_id')
                ->whereNotNull('profiles.city')
                ->where('profiles.city', '!=', '')
                ->select('profiles.city as value', DB::raw('count(*) as count'))
                ->groupBy('profiles.city')
                ->orderByDesc('count')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['value' => $r->value, 'count' => (int) $r->count])
                ->all();
        });
    }

    private function getSubjectsForProfile(Profile $profile, ?ProfileTeachingInfo $teachingInfo): array
    {
        $ids = $teachingInfo?->subjects_taught ?? $profile->subjects_taught ?? [];
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?? [];
        }
        if (empty($ids)) {
            return [];
        }
        return Subject::whereIn('id', $ids)->get(['id', 'name', 'slug', 'category'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug, 'category' => $s->category])
            ->all();
    }

    private function formatListItem(User $user): array
    {
        $profile = $user->profile;
        $teachingInfo = $profile?->teachingInfo;
        $options = PublicProfileOptionsMapper::mapTeacherOptions(
            $teachingInfo ?? (object) [],
            $teachingInfo,
            $profile
        );

        $subjects = $this->getSubjectsForProfile($profile ?? new Profile(), $teachingInfo);
        $bio = $profile?->bio ?? $teachingInfo?->bio ?? '';
        $hourlyRate = $teachingInfo?->hourly_rate ?? $profile?->hourly_rate ?? null;
        $city = $profile?->city ?? null;
        $state = $profile?->state ?? null;

        return [
            'id' => $user->id,
            'name' => $user->name ?? $profile?->display_name ?? 'Teacher',
            'bio' => Str::limit($bio, 120),
            'avatar_url' => $this->avatarUrl($profile, $user),
            'qualification' => $options['highest_qualification'],
            'experience_years' => $options['experience_years'],
            'rating' => (float) ($teachingInfo?->rating ?? 0),
            'total_reviews' => (int) ($teachingInfo?->total_reviews ?? 0),
            'hourly_rate' => $hourlyRate ? (float) $hourlyRate : null,
            'city' => $city,
            'state' => $state,
            'location' => $this->formatLocation($profile),
            'teaching_mode' => $options['teaching_mode'],
            'availability_status' => $options['availability_status'],
            'subjects' => $subjects,
            'institute' => null,
            'verified' => (bool) ($teachingInfo?->verified ?? false),
            'is_featured' => false,
        ];
    }

    private function formatShowItem(User $user, Profile $profile, ?ProfileTeachingInfo $teachingInfo, array $subjects): array
    {
        $options = PublicProfileOptionsMapper::mapTeacherOptions(
            $teachingInfo ?? (object) [],
            $teachingInfo,
            $profile
        );

        $reviews = $teachingInfo
            ? $teachingInfo->reviews()->where('status', 'published')->latest()->limit(5)->get()
            : collect();

        $reviewsFormatted = $reviews->map(fn ($r) => [
            'id' => $r->id,
            'rating' => $r->rating,
            'comment' => $r->comment,
            'created_at' => $r->created_at?->toIso8601String(),
        ])->all();

        // Full profile structure per ProfileApi.md (Get Profile, Update Basic, Location, Social, Teaching)
        $profileData = [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'first_name' => $profile->first_name,
            'last_name' => $profile->last_name,
            'display_name' => $profile->display_name,
            'bio' => $profile->bio ?? $teachingInfo?->bio,
            'date_of_birth' => $profile->date_of_birth?->format('Y-m-d'),
            'gender' => $options['gender'],
            'nationality' => $profile->nationality,
            'phone_primary' => $profile->phone_primary,
            'phone_secondary' => $profile->phone_secondary,
            'whatsapp' => $profile->whatsapp,
            'website' => $profile->website,
            'emergency_contact_name' => $profile->emergency_contact_name,
            'emergency_contact_phone' => $profile->emergency_contact_phone,
            'highest_qualification' => $options['highest_qualification'],
            'profile_image_url' => $this->avatarUrl($profile, $user),
            'profile_completion_percentage' => $profile->profile_completion_percentage,
        ];

        $location = $this->formatLocation($profile);

        $socialData = [
            'facebook_url' => $profile->facebook_url,
            'twitter_url' => $profile->twitter_url,
            'instagram_url' => $profile->instagram_url,
            'linkedin_url' => $profile->linkedin_url,
            'youtube_url' => $profile->youtube_url,
            'tiktok_url' => $profile->tiktok_url,
            'telegram_username' => $profile->telegram_username,
            'discord_username' => $profile->discord_username,
            'github_url' => $profile->github_url,
            'portfolio_url' => $profile->portfolio_url,
            'blog_url' => $profile->blog_url,
        ];

        $teachingData = [
            'qualification' => $options['highest_qualification'],
            'qualification_text' => $teachingInfo?->qualification ?? $profile->highest_qualification,
            'institution_name' => $profile->institution_name,
            'field_of_study' => $profile->field_of_study,
            'graduation_year' => $profile->graduation_year,
            'teaching_experience_years' => $options['experience_years'],
            'specialization' => $teachingInfo?->specialization,
            'languages' => $teachingInfo?->languages ?? [],
            'hourly_rate' => $teachingInfo?->hourly_rate ? (float) $teachingInfo->hourly_rate : null,
            'hourly_rate_range' => $options['hourly_rate_range'],
            'monthly_rate' => $teachingInfo?->monthly_rate ? (float) $teachingInfo->monthly_rate : null,
            'monthly_rate_range' => $options['monthly_rate_range'],
            'teaching_mode' => $options['teaching_mode'],
            'availability_status' => $options['availability_status'],
            'travel_radius_km' => $options['travel_radius_km'],
            'teaching_philosophy' => $profile->teaching_philosophy ?? $teachingInfo?->teaching_philosophy,
            'online_classes' => (bool) ($teachingInfo?->online_classes ?? $profile->online_classes ?? false),
            'home_tuition' => (bool) ($teachingInfo?->home_tuition ?? $profile->home_tuition ?? false),
            'institute_classes' => (bool) ($teachingInfo?->institute_classes ?? $profile->institute_classes ?? false),
        ];

        return [
            'id' => $user->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name ?? $profile->display_name ?? 'Teacher',
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'profile' => $profileData,
            'profile_image_url' => $this->avatarUrl($profile, $user),
            'completion_percentage' => $profile->profile_completion_percentage,
            'location' => $location,
            'social' => $socialData,
            'teaching' => $teachingData,
            'subjects' => $subjects,
            'portfolio' => $this->formatPortfolio($user->portfolio),
            'verified' => (bool) ($teachingInfo?->verified ?? false),
            'is_featured' => false,
        ];
    }

    /**
     * Format portfolio for public display. Returns null if no portfolio or status is not published.
     */
    private function formatPortfolio($portfolio): ?array
    {
        if (!$portfolio || $portfolio->status !== 'published') {
            return null;
        }

        return [
            'id' => $portfolio->id,
            'title' => $portfolio->title,
            'description' => $portfolio->description,
            'images' => $this->formatPortfolioImages($portfolio->images ?? []),
            'files' => $this->formatPortfolioFiles($portfolio->files ?? []),
            'category' => $portfolio->category,
            'categories_array' => $portfolio->categories_array ?? [],
            'tags' => $portfolio->tags,
            'tags_array' => $portfolio->tags_array ?? [],
            'url' => $portfolio->url,
            'is_featured' => (bool) $portfolio->is_featured,
            'order' => (int) ($portfolio->order ?? 0),
        ];
    }

    private function formatPortfolioImages(array $images): array
    {
        return array_map(fn (string $path) => [
            'path' => $path,
            'url' => storage_file_url($path),
        ], $images);
    }

    private function formatPortfolioFiles(array $files): array
    {
        return array_map(fn (string $path) => [
            'path' => $path,
            'url' => storage_file_url($path),
            'name' => basename($path),
        ], $files);
    }

    /**
     * Format location info per ProfileApi.md (Update Location).
     *
     * @return array{address_line_1: ?string, address_line_2: ?string, area: ?string, city: ?string, state: ?string, pincode: ?string, country_id: ?int, latitude: ?float, longitude: ?float}
     */
    private function formatLocation(?Profile $profile): array
    {
        if (!$profile) {
            return [
                'address_line_1' => null,
                'address_line_2' => null,
                'area' => null,
                'city' => null,
                'state' => null,
                'pincode' => null,
                'country_id' => null,
                'latitude' => null,
                'longitude' => null,
            ];
        }

        $countryId = $profile->country_id ? (int) $profile->country_id : null;
        return [
            'address_line_1' => $profile->address_line_1,
            'address_line_2' => $profile->address_line_2,
            'area' => $profile->area,
            'city' => $profile->city,
            'state' => $profile->state,
            'pincode' => $profile->pincode,
            'country_id' => $countryId,
            'country' => $countryId ? ProfileOptionsHelper::getOptionStructure('country', $countryId) : null,
            'latitude' => $profile->latitude ? (float) $profile->latitude : null,
            'longitude' => $profile->longitude ? (float) $profile->longitude : null,
        ];
    }

    private function avatarUrl(?Profile $profile, ?User $user): ?string
    {
        $path = $profile?->profile_image ?? $user?->profile_image ?? null;
        return $path ? storage_file_url((string) $path) : null;
    }
}
