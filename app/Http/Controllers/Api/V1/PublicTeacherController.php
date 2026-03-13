<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ProfileOptionsHelper;
use App\Helpers\PublicProfileOptionsMapper;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Traits\HandlesFileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicTeacherController extends BaseApiController
{
    use HandlesFileStorage;

    /**
     * Get filter options for teacher listing (all ID fields from config/options.php).
     */
    public function options(): JsonResponse
    {
        $optionKeys = [
            'gender',
            'teaching_mode',
            'availability_status',
            'hourly_rate_range',
            'monthly_rate_range',
            'teaching_experience_years',
            'travel_radius_km',
            'highest_qualification',
        ];

        $options = [];
        foreach ($optionKeys as $key) {
            $raw = config("options.{$key}", []);
            if (is_array($raw)) {
                $options[$key] = collect($raw)->map(fn ($label, $id) => [
                    'id' => is_numeric($id) ? (int) $id : $id,
                    'label' => $label,
                ])->values()->all();
            }
        }

        $subjects = Subject::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug]);

        $cities = TeacherProfile::query()
            ->where('verification_status', 'verified')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->whereNotNull('city')
            ->select('city', DB::raw('count(*) as count'))
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(50)
            ->get()
            ->map(fn ($r) => ['value' => $r->city, 'count' => $r->count]);

        return $this->success('Teacher filter options retrieved successfully.', [
            'options' => $options,
            'subjects' => $subjects,
            'cities' => $cities,
        ]);
    }

    /**
     * Get public list of teachers (paginated).
     *
     * Query params:
     * - per_page: 15 (default), max 50
     * - location: Filter by city or area
     * - city: Filter by city
     * - pincode: Filter by pincode
     * - subject_id: Filter by subject
     * - hourly_rate_range: Option ID (config/options.php)
     * - monthly_rate_range: Option ID (config/options.php)
     * - experience: teaching_experience_years option ID
     * - teaching_mode: teaching_mode option ID
     * - availability: availability_status option ID
     * - verified: 1 for verified only (default), 0 for all
     * - featured: 1 for featured only
     * - search: Search by name
     * - sort: created_at|rating|price_low|price_high|name
     * - order: asc|desc
     */
    public function index(Request $request): JsonResponse
    {
        $query = TeacherProfile::query()
            ->with([
                'user:id,name',
                'user.profile:id,user_id,profile_image,gender_id,highest_qualification',
                'user.profile.teachingInfo',
                'subjects:id,name,slug',
                'institute:id,institute_name,slug,city',
            ])
            ->whereHas('user', fn ($q) => $q->where('is_active', true));

        if ($request->boolean('verified', true)) {
            $query->where('verification_status', 'verified');
        }

        if ($location = trim((string) $request->query('location'))) {
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', "%{$location}%")
                    ->orWhere('teaching_city', 'like', "%{$location}%")
                    ->orWhereHas('user.profile', fn ($pq) => $pq->where('area', 'like', "%{$location}%"));
            });
        } elseif ($city = $request->query('city')) {
            $query->where(function ($q) use ($city) {
                $q->where('city', 'like', "%{$city}%")
                    ->orWhere('teaching_city', 'like', "%{$city}%");
            });
        }

        if ($pincode = $request->query('pincode')) {
            $query->whereHas('user.profile', fn ($q) => $q->where('pincode', $pincode));
        }

        if ($subjectId = $request->query('subject_id')) {
            $query->whereHas('subjects', fn ($q) => $q->where('subjects.id', $subjectId));
        }

        if ($hourlyRateRange = $request->query('hourly_rate_range')) {
            $query->whereHas('user.profile.teachingInfo', fn ($q) => $q->where('hourly_rate_id', $hourlyRateRange));
        }

        if ($monthlyRateRange = $request->query('monthly_rate_range')) {
            $query->whereHas('user.profile.teachingInfo', fn ($q) => $q->where('monthly_rate_id', $monthlyRateRange));
        }

        if ($experience = $request->query('experience')) {
            $query->where(function ($q) use ($experience) {
                $q->whereHas('user.profile.teachingInfo', fn ($tq) => $tq->where('teaching_experience_years', $experience))
                    ->orWhere('experience_years', $experience);
            });
        }

        if ($teachingMode = $request->query('teaching_mode')) {
            $query->whereHas('user.profile.teachingInfo', fn ($q) => $q->where('teaching_mode_id', $teachingMode));
        }

        if ($availability = $request->query('availability')) {
            $query->whereHas('user.profile.teachingInfo', fn ($q) => $q->where('availability_status_id', $availability));
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $sortBy = $request->query('sort', 'rating');
        $sortOrder = $request->query('order', 'desc');
        $this->applySorting($query, $sortBy, $sortOrder);

        $perPage = min((int) $request->query('per_page', 15), 50);
        $teachers = $query->paginate($perPage);

        $items = $teachers->getCollection()->map(fn ($t) => $this->formatTeacherListItem($t));

        return $this->success('Teachers retrieved successfully.', [
            'teachers' => $items,
            'pagination' => [
                'current_page' => $teachers->currentPage(),
                'per_page' => $teachers->perPage(),
                'total' => $teachers->total(),
                'last_page' => $teachers->lastPage(),
            ],
        ]);
    }

    /**
     * Get single teacher profile (by id or slug).
     */
    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $teacher = TeacherProfile::query()
            ->with([
                'user:id,name,email',
                'user.profile:id,user_id,profile_image,gender_id,highest_qualification',
                'user.profile.teachingInfo',
                'subjects:id,name,slug,category',
                'institute:id,institute_name,slug,city,address,website',
                'reviews' => fn ($q) => $q->where('status', 'published')->latest()->limit(5),
            ])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->when(
                is_numeric($idOrSlug),
                fn ($q) => $q->where('id', $idOrSlug),
                fn ($q) => $q->where('slug', $idOrSlug)
            )
            ->first();

        if (!$teacher) {
            return $this->notFound('Teacher not found.');
        }

        return $this->success('Teacher profile retrieved successfully.', $this->formatTeacherProfile($teacher));
    }

    /**
     * Apply sorting to TeacherProfile query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sortBy created_at|rating|price_low|price_high|name
     * @param string $sortOrder asc|desc
     */
    private function applySorting($query, string $sortBy, string $sortOrder): void
    {
        $order = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';

        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('teacher_profiles.hourly_rate', 'asc');
                break;
            case 'price_high':
                $query->orderBy('teacher_profiles.hourly_rate', 'desc');
                break;
            case 'name':
                $query->join('users', 'teacher_profiles.user_id', '=', 'users.id')
                    ->orderBy('users.name', $order)
                    ->select('teacher_profiles.*');
                break;
            case 'created_at':
                $query->orderBy('teacher_profiles.created_at', $order);
                break;
            case 'rating':
            default:
                $query->orderBy('teacher_profiles.rating', $order)
                    ->orderBy('teacher_profiles.total_reviews', 'desc');
                break;
        }
    }

    private function formatTeacherListItem(TeacherProfile $teacher): array
    {
        $user = $teacher->user;
        $avatarUrl = $teacher->avatar
            ? $this->getFileUrl($teacher->avatar)
            : ($user?->profile?->profile_image ? $this->getFileUrl($user->profile->profile_image) : null);

        if (!$avatarUrl && $user) {
            $avatarUrl = $user->avatar_url ?? null;
        }

        $options = PublicProfileOptionsMapper::mapTeacherOptions($teacher, $teacher->user?->profile?->teachingInfo, $teacher->user?->profile);

        return [
            'id' => $teacher->id,
            'slug' => $teacher->slug,
            'name' => $user?->name ?? 'Teacher',
            'bio' => Str::limit($teacher->bio ?? '', 120),
            'avatar_url' => $avatarUrl,
            'qualification' => $teacher->qualification ?? $teacher->qualifications,
            'experience_years' => $options['experience_years'],
            'rating' => (float) ($teacher->rating ?? 0),
            'total_reviews' => (int) ($teacher->total_reviews ?? 0),
            'hourly_rate' => $teacher->hourly_rate ? (float) $teacher->hourly_rate : null,
            'city' => $teacher->city ?? $teacher->teaching_city,
            'state' => $teacher->state,
            'teaching_mode' => $options['teaching_mode'],
            'availability_status' => $options['availability_status'],
            'travel_radius_km' => $options['travel_radius_km'],
            'hourly_rate_range' => $options['hourly_rate_range'],
            'monthly_rate_range' => $options['monthly_rate_range'],
            'gender' => $options['gender'],
            'highest_qualification' => $options['highest_qualification'],
            'availability_status' => $options['availability_status'],
            'subjects' => $teacher->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug]),
            'institute' => $teacher->institute
                ? [
                    'id' => $teacher->institute->id,
                    'name' => $teacher->institute->institute_name,
                    'slug' => $teacher->institute->slug,
                ]
                : null,
            'verified' => (bool) ($teacher->verified ?? ($teacher->verification_status === 'verified')),
            'is_featured' => (bool) $teacher->is_featured,
        ];
    }

    private function formatTeacherProfile(TeacherProfile $teacher): array
    {
        $user = $teacher->user;
        $avatarUrl = $teacher->avatar
            ? $this->getFileUrl($teacher->avatar)
            : ($user?->profile?->profile_image ? $this->getFileUrl($user->profile->profile_image) : null);

        if (!$avatarUrl && $user) {
            $avatarUrl = $user->avatar_url ?? null;
        }

        $reviews = $teacher->reviews->map(fn ($r) => [
            'id' => $r->id,
            'rating' => $r->rating,
            'comment' => $r->comment,
            'created_at' => $r->created_at?->toIso8601String(),
        ]);

        $teachingInfo = $user?->profile?->teachingInfo;
        $profile = $user?->profile;
        $options = PublicProfileOptionsMapper::mapTeacherOptions($teacher, $teachingInfo, $profile);

        return [
            'id' => $teacher->id,
            'slug' => $teacher->slug,
            'name' => $user?->name ?? 'Teacher',
            'email' => $user?->email,
            'bio' => $teacher->bio,
            'avatar_url' => $avatarUrl,
            'qualification' => $teacher->qualification ?? $teacher->qualifications,
            'experience_years' => $options['experience_years'],
            'specialization' => $teacher->specialization,
            'languages' => $teacher->languages ?? [],
            'rating' => (float) ($teacher->rating ?? 0),
            'total_reviews' => (int) ($teacher->total_reviews ?? 0),
            'total_students' => (int) ($teacher->total_students ?? 0),
            'hourly_rate' => $teacher->hourly_rate ? (float) $teacher->hourly_rate : null,
            'hourly_rate_range' => $options['hourly_rate_range'],
            'monthly_rate' => $teacher->monthly_rate ? (float) $teacher->monthly_rate : null,
            'monthly_rate_range' => $options['monthly_rate_range'],
            'teaching_mode' => $options['teaching_mode'],
            'online_classes' => (bool) $teacher->online_classes,
            'home_tuition' => (bool) $teacher->home_tuition,
            'institute_classes' => (bool) $teacher->institute_classes,
            'travel_radius_km' => $options['travel_radius_km'],
            'city' => $teacher->city ?? $teacher->teaching_city,
            'state' => $teacher->state,
            'availability_status' => $options['availability_status'],
            'gender' => $options['gender'],
            'highest_qualification' => $options['highest_qualification'],
            'subjects' => $teacher->subjects->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
                'category' => $s->category,
            ]),
            'institute' => $teacher->institute ? [
                'id' => $teacher->institute->id,
                'name' => $teacher->institute->institute_name,
                'slug' => $teacher->institute->slug,
                'city' => $teacher->institute->city,
                'address' => $teacher->institute->address,
                'website' => $teacher->institute->website,
            ] : null,
            'verified' => (bool) ($teacher->verified ?? ($teacher->verification_status === 'verified')),
            'is_featured' => (bool) $teacher->is_featured,
            'reviews_sample' => $reviews,
        ];
    }
}
