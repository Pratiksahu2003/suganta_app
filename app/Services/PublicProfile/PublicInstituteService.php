<?php

namespace App\Services\PublicProfile;

use App\Models\Institute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicInstituteService
{
    private const RELATED_CACHE_TTL = 300;

    private const LIST_RELATIONS = ['subjects:id,name,slug'];

    private const SHOW_RELATIONS = [
        'user:id,name,email',
        'user.profile.instituteInfo',
        'user.profile.socialLinks',
        'subjects:id,name,slug,category',
        'exams:id,name,slug',
    ];

    private const SHOW_COUNTS = ['teachers', 'childBranches', 'subjects'];

    private const SORT_COLUMNS = [
        'name'        => 'institute_name',
        'rating'      => 'rating',
        'established' => 'established_year',
        'students'    => 'total_students',
        'teachers'    => 'teachers_count',
        'recent'      => 'created_at',
    ];

    private const PROFILE_INFO_FILTERS = [
        'institute_type'          => 'institute_type_id',
        'institute_category'      => 'institute_category_id',
        'establishment_year_range' => 'establishment_year_id',
        'total_students_range'    => 'total_students_id',
        'total_teachers_range'    => 'total_teachers_id',
    ];

    // ─── List query ──────────────────────────────────────────────

    public function listQuery(Request $request): Builder
    {
        $query = $this->baseListQuery();

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        return $query;
    }

    public function baseListQuery(): Builder
    {
        return Institute::query()
            ->forPublicListing()
            ->with(self::LIST_RELATIONS)
            ->withCount('teachers');
    }

    // ─── Show query ──────────────────────────────────────────────

    public function findForShow(int $id): ?Institute
    {
        return Institute::query()
            ->forPublicListing()
            ->with([
                ...self::SHOW_RELATIONS,
                'childBranches' => fn ($q) => $q->where('is_active_branch', true)
                    ->select('id', 'parent_institute_id', 'institute_name', 'branch_name', 'branch_address', 'branch_city', 'branch_state', 'branch_phone', 'branch_email'),
                'teachers' => fn ($q) => $q->where('verification_status', 'verified')
                    ->with('user:id,name')
                    ->limit(10),
                'reviews' => fn ($q) => $q->where('status', 'published')
                    ->latest()
                    ->limit(5),
            ])
            ->withCount(self::SHOW_COUNTS)
            ->where('id', $id)
            ->first();
    }

    // ─── Fallback (empty filter results) ─────────────────────────

    public function getFeaturedFallback(array $excludeIds, int $limit): \Illuminate\Support\Collection
    {
        return $this->baseListQuery()
            ->whereNotIn('id', $excludeIds ?: [0])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->limit($limit)
            ->get();
    }

    // ─── Related institutes (cached) ─────────────────────────────

    public function getRelatedInstitutes(Institute $institute, int $limit = 6): array
    {
        return Cache::remember(
            "institute_related:{$institute->id}",
            self::RELATED_CACHE_TTL,
            fn () => $this->queryRelatedInstitutes($institute, $limit)->all()
        );
    }

    // ─── Filters ─────────────────────────────────────────────────

    private function applyFilters(Builder $query, Request $request): void
    {
        $this->applySearchFilter($query, $request);
        $this->applyLocationFilters($query, $request);
        $this->applyProfileInfoFilters($query, $request);
        $this->applySubjectFilter($query, $request);
        $this->applyBooleanFilters($query, $request);
    }

    private function applySearchFilter(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('search'));
        if ($search === '') {
            return;
        }

        $query->where(fn ($q) => $q
            ->where('institute_name', 'like', "%{$search}%")
            ->orWhere('branch_name', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%")
            ->orWhere('specialization', 'like', "%{$search}%")
            ->orWhere('affiliation', 'like', "%{$search}%"));
    }

    private function applyLocationFilters(Builder $query, Request $request): void
    {
        if ($request->filled('location')) {
            $loc = $request->get('location');
            $query->where(fn ($q) => $q
                ->where('city', 'like', "%{$loc}%")
                ->orWhere('branch_city', 'like', "%{$loc}%")
                ->orWhere('state', 'like', "%{$loc}%")
                ->orWhere('address', 'like', "%{$loc}%"));
            return;
        }

        if ($request->filled('city')) {
            $city = $request->get('city');
            $query->where(fn ($q) => $q
                ->where('city', 'like', "%{$city}%")
                ->orWhere('branch_city', 'like', "%{$city}%"));
        }

        if ($request->filled('state')) {
            $state = $request->get('state');
            $query->where(fn ($q) => $q
                ->where('state', 'like', "%{$state}%")
                ->orWhere('branch_state', 'like', "%{$state}%"));
        }

        if ($request->filled('pincode')) {
            $pin = $request->get('pincode');
            $query->where(fn ($q) => $q
                ->where('pincode', $pin)
                ->orWhere('branch_pincode', $pin));
        }
    }

    /**
     * Filters that map a request param to a ProfileInstituteInfo column via whereHas.
     * Driven by PROFILE_INFO_FILTERS constant -- zero repetition.
     */
    private function applyProfileInfoFilters(Builder $query, Request $request): void
    {
        foreach (self::PROFILE_INFO_FILTERS as $param => $column) {
            if ($request->filled($param)) {
                $value = $request->get($param);
                $query->whereHas('user.profile.instituteInfo', fn ($q) => $q->where($column, $value));
            }
        }
    }

    private function applySubjectFilter(Builder $query, Request $request): void
    {
        $subjectId = $request->query('subject_id') ?? $request->query('subject');
        if ($subjectId) {
            $query->whereHas('subjects', fn ($q) => $q->where('subjects.id', $subjectId));
        }
    }

    private function applyBooleanFilters(Builder $query, Request $request): void
    {
        if ($request->boolean('verified', false)) {
            $query->where('verified', true);
        }
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }
    }

    // ─── Sorting ─────────────────────────────────────────────────

    private function applySorting(Builder $query, Request $request): void
    {
        $sortBy = $request->query('sort', 'default');
        $order  = in_array(strtolower((string) $request->query('order', 'desc')), ['asc', 'desc'])
            ? strtolower($request->query('order', 'desc'))
            : 'desc';

        if ($sortBy === 'recent') {
            $query->orderByDesc('created_at');
            return;
        }

        if (isset(self::SORT_COLUMNS[$sortBy])) {
            $query->orderBy(self::SORT_COLUMNS[$sortBy], $order);
            return;
        }

        $query->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->orderByDesc('teachers_count');
    }

    // ─── Related query (private) ─────────────────────────────────

    private function queryRelatedInstitutes(Institute $institute, int $limit): \Illuminate\Support\Collection
    {
        $info = $institute->user?->profile?->instituteInfo;

        return $this->baseListQuery()
            ->where('id', '!=', $institute->id)
            ->where(function ($q) use ($institute, $info) {
                $city = $institute->city ?? $institute->branch_city;
                if ($city) {
                    $q->where('city', $city)->orWhere('branch_city', $city);
                }
                if ($info?->institute_type_id) {
                    $q->orWhereHas('user.profile.instituteInfo', fn ($iq) => $iq->where('institute_type_id', $info->institute_type_id));
                }
                if ($info?->institute_category_id) {
                    $q->orWhereHas('user.profile.instituteInfo', fn ($iq) => $iq->where('institute_category_id', $info->institute_category_id));
                }
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->limit($limit)
            ->get();
    }
}
