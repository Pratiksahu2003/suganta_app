<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\FilterOptionsHelper;
use App\Models\Institute;
use App\Services\PublicProfile\PublicInstituteFormatter;
use App\Services\PublicProfile\PublicInstituteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicInstituteController extends BaseApiController
{
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
            'cities'   => FilterOptionsHelper::getInstituteCities(),
        ]);
    }

    /**
     * Get public list of institutes (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $query   = $this->service->listQuery($request);
        $perPage = min(max(1, (int) $request->query('per_page', 15)), 50);

        $paginator = $query->paginate($perPage)->withQueryString();
        $institutes = collect($paginator->items());
        $usedFallback = false;

        if ($institutes->isEmpty()) {
            $institutes = $this->service
                ->getFeaturedFallback($paginator->pluck('id')->all(), $perPage)
                ->unique('id')
                ->values();
            $usedFallback = true;
        }

        $items = $institutes->map(fn (Institute $i) => $this->formatter->listItem($i));

        $pagination = $usedFallback
            ? FilterOptionsHelper::fallbackPaginationMeta($request, $perPage, $institutes->count())
            : FilterOptionsHelper::paginationMeta($paginator);

        return $this->success('Institutes retrieved successfully.', [
            'institutes' => $items,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Get single institute profile by ID.
     */
    public function show(int $id): JsonResponse
    {
        $institute = $this->service->findForShow($id);

        if (!$institute) {
            return $this->notFound('Institute not found.');
        }

        $related = collect($this->service->getRelatedInstitutes($institute))
            ->map(fn (Institute $i) => $this->formatter->listItem($i))
            ->all();

        return $this->success('Institute profile retrieved successfully.', [
            ...$this->formatter->show($institute),
            'related_institutes' => $related,
        ]);
    }
}
