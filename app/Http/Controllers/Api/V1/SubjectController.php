<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $query = Subject::query()
            ->select('id', 'name')
            ->orderBy('name');

        if ($search = $request->query('search')) {
            $search = trim($search);
            if ($search !== '') {
                $query->where('name', 'like', '%' . $search . '%');
            }
        }

        $subjects = $query->get();

        return $this->success('Subjects retrieved successfully.', $subjects);
    }
}
