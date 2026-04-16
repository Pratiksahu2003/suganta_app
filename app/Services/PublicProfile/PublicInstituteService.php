<?php

namespace App\Services\PublicProfile;

use App\Models\User;
use App\Support\CacheVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicInstituteService
{
    private const RELATED_CACHE_TTL = 300;

    private const LIST_RELATIONS = [
        'profile:id,user_id,display_name,first_name,last_name,bio,city,state,area,pincode,profile_image,slug,is_active,is_featured,is_verified,latitude,longitude,website,phone_primary,whatsapp',
        'profile.instituteInfo',
    ];

    private const SHOW_RELATIONS = [
        'profile',
        'profile.instituteInfo',
        'portfolio'
    ];

    private const SORT_MAP = [
        'name_asc'        => ['table' => 'profile_institute_info', 'column' => 'institute_name', 'direction' => 'asc'],
        'name_desc'       => ['table' => 'profile_institute_info', 'column' => 'institute_name', 'direction' => 'desc'],
        'established_asc' => ['table' => 'profile_institute_info', 'column' => 'establishment_year_id', 'direction' => 'asc'],
        'established_desc'=> ['table' => 'profile_institute_info', 'column' => 'establishment_year_id', 'direction' => 'desc'],
        'students_asc'    => ['table' => 'profile_institute_info', 'column' => 'total_students_id', 'direction' => 'asc'],
        'students_desc'   => ['table' => 'profile_institute_info', 'column' => 'total_students_id', 'direction' => 'desc'],
    ];

    /**
     * Param => profile_institute_info column mapping for DRY whereHas filters.
     */
    private const INSTITUTE_INFO_FILTERS = [
        'institute_type'           => 'institute_type_id',
        'type'                     => 'institute_type_id',
        'institute_category'       => 'institute_category_id',
        'category'                 => 'institute_category_id',
        'establishment_year_range' => 'establishment_year_id',
        'established'              => 'establishment_year_id',
        'total_students_range'     => 'total_students_id',
        'total_teachers_range'     => 'total_teachers_id',
        'total_teachers'           => 'total_teachers_id',
    ];

    // ─── List ────────────────────────────────────────────────────

    public function listQuery(Request $request): Builder
    {
        $query = $this->baseListQuery();
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);
        return $query;
    }

    public function baseListQuery(): Builder
    {
        return User::with(self::LIST_RELATIONS)
            ->whereIn('role', ['institute', 'university'])
            ->whereNotNull('email_verified_at')
            ->whereIn('registration_fee_status', ['paid', 'not_required']);
    }

    // ─── Show ────────────────────────────────────────────────────

    public function findForShow(int $id): ?User
    {
        return User::with(self::SHOW_RELATIONS)
            ->whereIn('role', ['institute', 'university'])
            ->whereNotNull('email_verified_at')
            ->whereIn('registration_fee_status', ['paid', 'not_required'])
            ->where('id', $id)
            ->first();
    }

    public function findForShowBySlug(string $slug): ?User
    {
        return User::with(self::SHOW_RELATIONS)
            ->whereIn('role', ['institute', 'university'])
            ->whereNotNull('email_verified_at')
            ->whereIn('registration_fee_status', ['paid', 'not_required'])
            ->whereHas('profile', fn ($q) => $q->where('slug', $slug))
            ->first();
    }

    // ─── Fallback ────────────────────────────────────────────────

    public function getFeaturedFallback(array $excludeIds, int $limit): \Illuminate\Support\Collection
    {
        return $this->baseListQuery()
            ->whereNotIn('users.id', $excludeIds ?: [0])
            ->orderByDesc('users.created_at')
            ->limit($limit)
            ->get();
    }

    // ─── Related (cached) ────────────────────────────────────────

    public function getRelatedInstitutes(User $user, int $limit = 4): array
    {
        $version = CacheVersion::get('institutes_public');

        return Cache::remember(
            "institute_related:v{$version}:{$user->id}",
            self::RELATED_CACHE_TTL,
            fn () => $this->queryRelatedInstitutes($user, $limit)->all()
        );
    }

    // ─── Cities for filters ──────────────────────────────────────

    public function getInstituteCities(int $limit = 50): array
    {
        $version = CacheVersion::get('institutes_public');
        return Cache::remember("institute_cities_profile:v{$version}", 3600, function () use ($limit) {
            return User::whereIn('role', ['institute', 'university'])
                ->whereNotNull('email_verified_at')
                ->whereIn('registration_fee_status', ['paid', 'not_required'])
                ->join('profiles', 'users.id', '=', 'profiles.user_id')
                ->where('profiles.is_active', true)
                ->whereNotNull('profiles.city')
                ->where('profiles.city', '!=', '')
                ->select('profiles.city as value', DB::raw('count(*) as count'))
                ->groupBy('profiles.city')
                ->orderByDesc('count')
                ->orderBy('profiles.city')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => ['value' => $r->value, 'count' => (int) $r->count])
                ->all();
        });
    }

    // ─── Filters ─────────────────────────────────────────────────

    private function applyFilters(Builder $query, Request $request): void
    {
        $this->applySearchFilter($query, $request);
        $this->applyLocationFilters($query, $request);
        $this->applyInstituteInfoFilters($query, $request);
        $this->applyBooleanFilters($query, $request);
    }

    private function applySearchFilter(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('search'));
        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('users.name', 'like', "%{$search}%")
                ->orWhereHas('profile', fn ($pq) => $pq
                    ->where('display_name', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%"))
                ->orWhereHas('profile.instituteInfo', fn ($iq) => $iq
                    ->where('institute_name', 'like', "%{$search}%")
                    ->orWhere('institute_description', 'like', "%{$search}%"));
        });
    }

    private function applyLocationFilters(Builder $query, Request $request): void
    {
        if ($request->filled('location')) {
            $loc = $request->get('location');
            $query->whereHas('profile', fn ($q) => $q
                ->where('city', 'like', "%{$loc}%")
                ->orWhere('area', 'like', "%{$loc}%")
                ->orWhere('state', 'like', "%{$loc}%"));
            return;
        }

        if ($request->filled('city')) {
            $query->whereHas('profile', fn ($q) => $q->where('city', 'like', "%{$request->get('city')}%"));
        }

        if ($request->filled('area') && !$request->filled('city')) {
            $query->whereHas('profile', fn ($q) => $q->where('area', 'like', "%{$request->get('area')}%"));
        }

        if ($request->filled('state')) {
            $query->whereHas('profile', fn ($q) => $q->where('state', 'like', "%{$request->get('state')}%"));
        }

        if ($request->filled('pincode')) {
            $query->whereHas('profile', fn ($q) => $q->where('pincode', $request->get('pincode')));
        }
    }

    /**
     * DRY: all profile_institute_info column filters driven by INSTITUTE_INFO_FILTERS map.
     */
    private function applyInstituteInfoFilters(Builder $query, Request $request): void
    {
        foreach (self::INSTITUTE_INFO_FILTERS as $param => $column) {
            if ($request->filled($param)) {
                $value = $request->get($param);
                $query->whereHas('profile.instituteInfo', fn ($q) => $q->where($column, $value));
            }
        }
    }

    private function applyBooleanFilters(Builder $query, Request $request): void
    {
        if ($request->boolean('verified', false)) {
            $query->whereHas('profile', fn ($q) => $q->where('is_verified', true));
        }
        if ($request->boolean('featured')) {
            $query->whereHas('profile', fn ($q) => $q->where('is_featured', true));
        }
    }

    // ─── Sorting ─────────────────────────────────────────────────

    private function applySorting(Builder $query, Request $request): void
    {
        $orderBy = $request->query('order_by') ?? $request->query('sort', 'recent');

        if ($orderBy === 'recent' || $orderBy === 'default') {
            $query->orderByDesc('users.created_at');
            return;
        }

        if (isset(self::SORT_MAP[$orderBy])) {
            $map = self::SORT_MAP[$orderBy];
            $subquery = "(SELECT {$map['table']}.{$map['column']} FROM profiles "
                . "LEFT JOIN {$map['table']} ON profiles.id = {$map['table']}.profile_id "
                . "WHERE profiles.user_id = users.id LIMIT 1)";
            $query->orderByRaw("{$subquery} {$map['direction']}");
            return;
        }

        $query->orderByDesc('users.created_at');
    }

    // ─── Related (private) ───────────────────────────────────────

    private function queryRelatedInstitutes(User $user, int $limit): \Illuminate\Support\Collection
    {
        $profile = $user->profile;
        $info = $profile?->instituteInfo;

        return $this->baseListQuery()
            ->where('users.id', '!=', $user->id)
            ->whereHas('profile', function ($q) use ($profile, $info) {
                $q->where('is_active', true)
                    ->where(function ($sub) use ($profile, $info) {
                        if ($profile?->city) {
                            $sub->where('city', $profile->city);
                        }
                        if ($info?->institute_type_id) {
                            $sub->orWhereHas('instituteInfo', fn ($iq) => $iq->where('institute_type_id', $info->institute_type_id));
                        }
                        if ($info?->institute_category_id) {
                            $sub->orWhereHas('instituteInfo', fn ($iq) => $iq->where('institute_category_id', $info->institute_category_id));
                        }
                    });
            })
            ->orderByDesc('users.created_at')
            ->limit($limit)
            ->get();
    }
}
