<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\PublicProfileOptionsMapper;
use App\Models\TeacherProfile;
use App\Traits\HandlesFileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicTeacherController extends BaseApiController
{
    use HandlesFileStorage;

    /**
     * Get public list of teachers (paginated).
     *
     * Query params:
     * - per_page: 15 (default)
     * - city: Filter by city
     * - subject_id: Filter by subject
     * - verified: 1 for verified only, 0 for all
     * - search: Search by name
     */
    public function index(Request $request): JsonResponse
    {
        $query = TeacherProfile::query()
            ->with(['user:id,name', 'user.profile:id,user_id,profile_image', 'subjects:id,name,slug', 'institute:id,institute_name,slug,city'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true));

        if ($request->boolean('verified', true)) {
            $query->where('verification_status', 'verified');
        }

        if ($city = $request->query('city')) {
            $query->where(function ($q) use ($city) {
                $q->where('city', 'like', "%{$city}%")
                    ->orWhere('teaching_city', 'like', "%{$city}%");
            });
        }

        if ($subjectId = $request->query('subject_id')) {
            $query->whereHas('subjects', fn ($q) => $q->where('subjects.id', $subjectId));
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $perPage = min((int) $request->query('per_page', 15), 50);
        $teachers = $query->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->orderByDesc('total_reviews')
            ->paginate($perPage);

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
                'user.profile:id,user_id,profile_image',
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

    private function formatTeacherListItem(TeacherProfile $teacher): array
    {
        $user = $teacher->user;
        $avatarUrl = $teacher->avatar
            ? $this->getFileUrl($teacher->avatar)
            : ($user?->profile?->profile_image ? $this->getFileUrl($user->profile->profile_image) : null);

        if (!$avatarUrl && $user) {
            $avatarUrl = $user->avatar_url ?? null;
        }

        $options = PublicProfileOptionsMapper::mapTeacherOptions($teacher, $teacher->user?->profile?->teachingInfo);

        return [
            'id' => $teacher->id,
            'slug' => $teacher->slug,
            'name' => $user?->name ?? 'Teacher',
            'bio' => Str::limit($teacher->bio ?? '', 120),
            'avatar_url' => $avatarUrl,
            'qualification' => $teacher->qualification ?? $teacher->qualifications,
            'experience_years' => $teacher->experience_years,
            'rating' => (float) ($teacher->rating ?? 0),
            'total_reviews' => (int) ($teacher->total_reviews ?? 0),
            'hourly_rate' => $teacher->hourly_rate ? (float) $teacher->hourly_rate : null,
            'city' => $teacher->city ?? $teacher->teaching_city,
            'state' => $teacher->state,
            'teaching_mode' => $options['teaching_mode'],
            'travel_radius_km' => $options['travel_radius_km'],
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
        $options = PublicProfileOptionsMapper::mapTeacherOptions($teacher, $teachingInfo);

        return [
            'id' => $teacher->id,
            'slug' => $teacher->slug,
            'name' => $user?->name ?? 'Teacher',
            'email' => $user?->email,
            'bio' => $teacher->bio,
            'avatar_url' => $avatarUrl,
            'qualification' => $teacher->qualification ?? $teacher->qualifications,
            'experience_years' => $teacher->experience_years,
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
            'experience_years' => $options['experience_years'],
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
