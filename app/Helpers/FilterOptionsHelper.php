<?php

namespace App\Helpers;

use App\Models\Institute;
use App\Models\Subject;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * DRY helper for building filter options from config and common queries.
 * Options are cached 1 hour for performance.
 */
class FilterOptionsHelper
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Build options array from config keys (e.g. gender, teaching_mode).
     */
    public static function buildFromConfig(array $keys): array
    {
        $sorted = $keys;
        sort($sorted);
        $cacheKey = 'filter_options:' . implode(',', $sorted);
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($keys) {
            $options = [];
            foreach ($keys as $key) {
                $raw = config("options.{$key}", []);
                if (is_array($raw)) {
                    $options[$key] = collect($raw)->map(fn ($label, $id) => [
                        'id' => is_numeric($id) ? (int) $id : $id,
                        'label' => $label,
                    ])->values()->all();
                }
            }
            return $options;
        });
    }

    /**
     * Get active subjects formatted for dropdowns (cached).
     */
    public static function getActiveSubjects(): array
    {
        return Cache::remember('filter_subjects', self::CACHE_TTL, function () {
            return Subject::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug])
                ->all();
        });
    }

    /**
     * Get cities from a query (caller must set select/groupBy; we add order/limit).
     */
    public static function getCitiesFromQuery(Builder $query, int $limit = 50): array
    {
        return $query->orderByDesc('count')->limit($limit)->get()
            ->map(fn ($r) => ['value' => $r->city ?? $r->value ?? null, 'count' => (int) ($r->count ?? 0)])
            ->filter(fn ($r) => $r['value'] !== null)
            ->values()
            ->all();
    }

    /** Cities for teacher filters. */
    public static function getTeacherCities(int $limit = 50): array
    {
        $query = TeacherProfile::query()
            ->where('verification_status', 'verified')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->whereNotNull('city')
            ->select('city', DB::raw('count(*) as count'))
            ->groupBy('city');
        return self::getCitiesFromQuery($query, $limit);
    }

    /** Cities for institute filters. */
    public static function getInstituteCities(int $limit = 50): array
    {
        $query = Institute::query()
            ->where(fn ($q) => $q->whereNull('parent_institute_id')->orWhere('institute_type', 'main'))
            ->where(fn ($q) => $q->whereNull('user_id')->orWhereHas('user', fn ($uq) => $uq->where('is_active', true)))
            ->where(fn ($q) => $q->whereNotNull('city')->where('city', '!=', '')
                ->orWhere(fn ($q2) => $q2->whereNotNull('branch_city')->where('branch_city', '!=', '')))
            ->select(DB::raw('COALESCE(city, branch_city) as city'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('COALESCE(city, branch_city)'));
        return self::getCitiesFromQuery($query, $limit);
    }

    /**
     * Standard pagination meta for API responses.
     * Includes URLs for proper pagination support in mobile/web clients.
     */
    public static function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => max(1, $paginator->lastPage()),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'first_page_url' => $paginator->url(1),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];
    }

    /**
     * Pagination meta for fallback results (when filters return empty and we serve defaults).
     * Matches paginationMeta() structure for frontend consistency.
     */
    public static function fallbackPaginationMeta(Request $request, int $perPage, int $total): array
    {
        $baseUrl = $request->url() . '?' . http_build_query($request->except('page') + ['page' => 1]);

        return [
            'current_page' => 1,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => 1,
            'from' => $total > 0 ? 1 : null,
            'to' => $total > 0 ? $total : null,
            'first_page_url' => $baseUrl,
            'last_page_url' => $baseUrl,
            'next_page_url' => null,
            'prev_page_url' => null,
        ];
    }
}
