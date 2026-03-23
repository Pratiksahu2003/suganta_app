<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FilterOptionsHelper;
use App\Models\User;
use App\Services\PublicProfile\PublicInstituteFormatter;
use App\Services\PublicProfile\PublicInstituteService;
use App\Support\CacheVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicInstituteController extends BaseApiController
{
    private const LIST_CACHE_TTL_SECONDS = 120;
    private const SHOW_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private PublicInstituteService $service,
        private PublicInstituteFormatter $formatter,
    ) {}

    /**
     * Get filter options for institute listing.
     */
    public function options(): JsonResponse
    {
        $optionKeys = [
            'institute_type', 'institute_category', 'establishment_year_range',
            'total_students_range', 'total_teachers_range',
        ];

        return $this->success('Institute filter options retrieved successfully.', [
            'options'  => FilterOptionsHelper::buildFromConfig($optionKeys),
            'subjects' => FilterOptionsHelper::getActiveSubjects(),
            'cities'   => $this->service->getInstituteCities(),
        ]);
    }

    /**
     * Get public list of institutes (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $queryParams = $request->query();
        ksort($queryParams);
        $version = CacheVersion::get('institutes_public');
        $cacheKey = "institutes:list:v{$version}:" . md5(json_encode($queryParams));

        $payload = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::LIST_CACHE_TTL_SECONDS),
            function () use ($request) {
                $query   = $this->service->listQuery($request);
                $perPage = min(max(1, (int) $request->query('per_page', 12)), 50);

                $paginator  = $query->paginate($perPage)->withQueryString();
                $institutes = collect($paginator->items());
                $usedFallback = false;

                if ($institutes->isEmpty()) {
                    $institutes = $this->service
                        ->getFeaturedFallback($paginator->pluck('id')->all(), $perPage)
                        ->unique('id')
                        ->values();
                    $usedFallback = true;
                }

                $items = $institutes->map(fn (User $u) => $this->formatter->listItem($u));

                $pagination = $usedFallback
                    ? FilterOptionsHelper::fallbackPaginationMeta($request, $perPage, $institutes->count())
                    : FilterOptionsHelper::paginationMeta($paginator);

                return [
                    'institutes' => $items,
                    'pagination' => $pagination,
                ];
            }
        );

        return $this->success('Institutes retrieved successfully.', $payload);
    }

    /**
     * Get single institute profile by ID.
     */
    public function show(int $id): JsonResponse
    {
        $version = CacheVersion::get('institutes_public');
        $cacheKey = "institutes:show:v{$version}:{$id}";
        $payload = Cache::remember($cacheKey, now()->addSeconds(self::SHOW_CACHE_TTL_SECONDS), function () use ($id) {
            $user = $this->service->findForShow($id);

            if (!$user || !$user->profile) {
                return null;
            }

            $related = collect($this->service->getRelatedInstitutes($user))
                ->map(fn (User $u) => $this->formatter->listItem($u))
                ->all();

            return [
                ...$this->formatter->show($user),
                'related_institutes' => $related,
            ];
        });

        if ($payload === null) {
            return $this->notFound('Institute not found.');
        }

        return $this->success('Institute profile retrieved successfully.', $payload);
    }

    
  
}
