<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\PublicProfileOptionsMapper;
use App\Models\Institute;
use App\Traits\HandlesFileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicInstituteController extends BaseApiController
{
    use HandlesFileStorage;

    /**
     * Get public list of institutes (paginated).
     *
     * Query params:
     * - per_page: 15 (default)
     * - city: Filter by city
     * - verified: 1 for verified only, 0 for all
     * - search: Search by institute name
     * - featured: 1 for featured only
     */
    public function index(Request $request): JsonResponse
    {
        $query = Institute::query()
            ->with(['subjects:id,name,slug'])
            ->withCount('teachers')
            ->where(function ($q) {
                $q->whereNull('parent_institute_id')
                    ->orWhere('institute_type', 'main');
            });

        if ($request->boolean('verified', false)) {
            $query->where('verified', true);
        }

        if ($city = $request->query('city')) {
            $query->where(function ($q) use ($city) {
                $q->where('city', 'like', "%{$city}%")
                    ->orWhere('branch_city', 'like', "%{$city}%");
            });
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('institute_name', 'like', "%{$search}%")
                    ->orWhere('branch_name', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $perPage = min((int) $request->query('per_page', 15), 50);
        $institutes = $query->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->orderByDesc('teachers_count')
            ->paginate($perPage);

        $items = $institutes->getCollection()->map(fn ($i) => $this->formatInstituteListItem($i));

        return $this->success('Institutes retrieved successfully.', [
            'institutes' => $items,
            'pagination' => [
                'current_page' => $institutes->currentPage(),
                'per_page' => $institutes->perPage(),
                'total' => $institutes->total(),
                'last_page' => $institutes->lastPage(),
            ],
        ]);
    }

    /**
     * Get single institute profile (by id or slug).
     */
    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $institute = Institute::query()
            ->with([
                'user:id,name,email',
                'user.profile.instituteInfo',
                'subjects:id,name,slug,category',
                'childBranches' => fn ($q) => $q->where('is_active_branch', true)->select('id', 'parent_institute_id', 'institute_name', 'branch_name', 'slug', 'branch_address', 'branch_city', 'branch_state', 'branch_phone', 'branch_email'),
                'teachers' => fn ($q) => $q->where('verification_status', 'verified')->with('user:id,name')->limit(10),
            ])
            ->withCount('teachers')
            ->where(function ($q) {
                $q->whereNull('parent_institute_id')
                    ->orWhere('institute_type', 'main');
            })
            ->when(
                is_numeric($idOrSlug),
                fn ($q) => $q->where('id', $idOrSlug),
                fn ($q) => $q->where('slug', $idOrSlug)
            )
            ->first();

        if (!$institute) {
            return $this->notFound('Institute not found.');
        }

        return $this->success('Institute profile retrieved successfully.', $this->formatInstituteProfile($institute));
    }

    private function formatInstituteListItem(Institute $institute): array
    {
        $logoUrl = $institute->logo
            ? $this->getFileUrl($institute->logo)
            : null;

        return [
            'id' => $institute->id,
            'slug' => $institute->slug,
            'name' => $institute->display_name,
            'description' => Str::limit($institute->description ?? $institute->specialization ?? '', 150),
            'logo_url' => $logoUrl,
            'city' => $institute->city ?? $institute->branch_city,
            'state' => $institute->state ?? $institute->branch_state,
            'rating' => (float) ($institute->rating ?? 0),
            'teachers_count' => $institute->teachers_count ?? $institute->teachers()->count(),
            'subjects' => $institute->subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug])->take(5),
            'verified' => (bool) $institute->verified,
            'is_featured' => (bool) $institute->is_featured,
        ];
    }

    private function formatInstituteProfile(Institute $institute): array
    {
        $logoUrl = $institute->logo
            ? $this->getFileUrl($institute->logo)
            : null;

        $galleryUrls = [];
        if (!empty($institute->gallery_images) && is_array($institute->gallery_images)) {
            foreach ($institute->gallery_images as $img) {
                if (is_string($img)) {
                    $galleryUrls[] = $this->getFileUrl($img);
                }
            }
        }

        $branches = $institute->childBranches->map(fn ($b) => [
            'id' => $b->id,
            'name' => $b->branch_name ?: $b->institute_name,
            'slug' => $b->slug,
            'address' => $b->branch_address,
            'city' => $b->branch_city,
            'state' => $b->branch_state,
            'phone' => $b->branch_phone,
            'email' => $b->branch_email,
        ]);

        $teachersPreview = $institute->teachers->map(fn ($t) => [
            'id' => $t->id,
            'slug' => $t->slug,
            'name' => $t->user?->name ?? 'Teacher',
        ]);

        $instituteInfo = $institute->user?->profile?->instituteInfo;
        $options = PublicProfileOptionsMapper::mapInstituteOptions($institute, $instituteInfo);

        return [
            'id' => $institute->id,
            'slug' => $institute->slug,
            'name' => $institute->display_name,
            'description' => $institute->description,
            'specialization' => $institute->specialization,
            'affiliation' => $institute->affiliation,
            'registration_number' => $institute->registration_number,
            'website' => $institute->website,
            'contact_person' => $institute->contact_person,
            'contact_phone' => $institute->contact_phone,
            'contact_email' => $institute->contact_email,
            'address' => $institute->address ?? $institute->branch_address,
            'city' => $institute->city ?? $institute->branch_city,
            'state' => $institute->state ?? $institute->branch_state,
            'pincode' => $institute->pincode ?? $institute->branch_pincode,
            'established_year' => $institute->established_year,
            'institute_type' => $options['institute_type'],
            'institute_category' => $options['institute_category'],
            'establishment_year' => $options['establishment_year'],
            'total_students' => $institute->total_students,
            'total_students_range' => $options['total_students'],
            'total_teachers_range' => $options['total_teachers'],
            'rating' => (float) ($institute->rating ?? 0),
            'logo_url' => $logoUrl,
            'gallery_urls' => $galleryUrls,
            'facilities' => $institute->facilities ?? [],
            'subjects' => $institute->subjects->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
                'category' => $s->category,
            ]),
            'branches' => $branches,
            'teachers_count' => $institute->teachers_count ?? $institute->teachers()->count(),
            'teachers_preview' => $teachersPreview,
            'verified' => (bool) $institute->verified,
            'is_featured' => (bool) $institute->is_featured,
        ];
    }
}
