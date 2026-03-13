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

        if ($teachers->isEmpty()) {
            $onlineTeachers = $this->getOnlineTeachersExcluding($teacherIds, 10);
            $teachers = $teachers->merge($onlineTeachers)->unique('id')->values();
        }

        $items = $teachers->map(fn (User $user) => $this->formatListItem($user));

        return $this->success('Teachers retrieved successfully.', [
            'teachers' => $items,
            'pagination' => FilterOptionsHelper::paginationMeta($teachersPaginator),
        ]);
    }

    /**
     * Get single teacher profile by User ID.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['profile', 'profile.teachingInfo', 'profile.socialLinks'])
            ->where('role', 'teacher')
            ->whereNotNull('email_verified_at')
            ->where('id', '!=', self::EXCLUDED_USER_ID)
            ->whereIn('registration_fee_status', ['paid', 'not_required'])
            ->where('id', $id)
            ->where('is_active', true)
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
            ->whereIn('registration_fee_status', ['paid', 'not_required'])
            ->where('is_active', true);
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
            ->where('is_active', true)
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
            ->where('is_active', true);

        $query->where(function ($q) use ($subjectIds, $city) {
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
                ->where('is_active', true)
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
            'qualification' => $profile?->highest_qualification ?? $teachingInfo?->qualification ?? null,
            'experience_years' => $options['experience_years'],
            'rating' => (float) ($teachingInfo?->rating ?? 0),
            'total_reviews' => (int) ($teachingInfo?->total_reviews ?? 0),
            'hourly_rate' => $hourlyRate ? (float) $hourlyRate : null,
            'city' => $city,
            'state' => $state,
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

        return [
            'id' => $user->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name ?? $profile->display_name ?? 'Teacher',
                'email' => $user->email,
            ],
            'profile' => [
                'bio' => $profile->bio ?? $teachingInfo?->bio,
                'profile_image_url' => $this->avatarUrl($profile, $user),
                'phone_primary' => $profile->phone_primary,
                'whatsapp' => $profile->whatsapp,
                'city' => $profile->city,
                'state' => $profile->state,
                'pincode' => $profile->pincode,
                'gender' => $options['gender'],
                'highest_qualification' => $options['highest_qualification'],
            ],
            'teaching' => [
                'qualification' => $teachingInfo?->qualification ?? $profile->highest_qualification,
                'experience_years' => $options['experience_years'],
                'specialization' => $teachingInfo?->specialization,
                'languages' => $teachingInfo?->languages ?? [],
                'hourly_rate' => $teachingInfo?->hourly_rate ? (float) $teachingInfo->hourly_rate : null,
                'hourly_rate_range' => $options['hourly_rate_range'],
                'monthly_rate' => $teachingInfo?->monthly_rate ? (float) $teachingInfo->monthly_rate : null,
                'monthly_rate_range' => $options['monthly_rate_range'],
                'teaching_mode' => $options['teaching_mode'],
                'availability_status' => $options['availability_status'],
                'travel_radius_km' => $options['travel_radius_km'],
                'online_classes' => (bool) ($teachingInfo?->online_classes ?? $profile->online_classes ?? false),
                'home_tuition' => (bool) ($teachingInfo?->home_tuition ?? $profile->home_tuition ?? false),
                'institute_classes' => (bool) ($teachingInfo?->institute_classes ?? $profile->institute_classes ?? false),
            ],
            'rating' => (float) ($teachingInfo?->rating ?? 0),
            'total_reviews' => (int) ($teachingInfo?->total_reviews ?? 0),
            'total_students' => (int) ($teachingInfo?->total_students ?? $profile->total_students ?? 0),
            'subjects' => $subjects,
            'institute' => null,
            'verified' => (bool) ($teachingInfo?->verified ?? false),
            'is_featured' => false,
            'reviews_sample' => $reviewsFormatted,
        ];
    }

    private function avatarUrl(?Profile $profile, ?User $user): ?string
    {
        $path = $profile?->profile_image ?? $user?->profile_image ?? null;
        return $path ? storage_file_url((string) $path) : null;
    }
}
