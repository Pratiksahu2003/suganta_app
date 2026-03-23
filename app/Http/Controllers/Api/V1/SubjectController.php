<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Subject;
use App\Support\CacheVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SubjectController extends BaseApiController
{
    /**
     * Get list of subjects (id, name only) with optional search.
     *
     * Query params:
     * - search: Filter subjects by name (partial match)
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $version = CacheVersion::get('subjects');
        $cacheKey = "subjects:index:v{$version}:" . md5(strtolower($search));

        $subjects = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($search) {
            $query = Subject::query()
                ->select('id', 'name')
                ->orderBy('name');

            if ($search !== '') {
                $query->where('name', 'like', '%' . $search . '%');
            }

            return $query->get();
        });

        return $this->success('Subjects retrieved successfully.', $subjects);
    }
}
