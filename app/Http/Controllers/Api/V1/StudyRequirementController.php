<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\StudyRequirement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudyRequirementController extends BaseApiController
{
    /**
     * Get paginated list of study requirements.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StudyRequirement::query()
            ->with(['user:id,name,email'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->status($request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_id', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('student_name', 'like', "%{$search}%")
                    ->orWhere('location_city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('learning_mode')) {
            $query->where('learning_mode', $request->string('learning_mode'));
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $requirements = $query->paginate($perPage);

        return $this->success('Study requirements retrieved successfully.', [
            'data' => $requirements->items(),
            'meta' => [
                'current_page' => $requirements->currentPage(),
                'last_page' => $requirements->lastPage(),
                'per_page' => $requirements->perPage(),
                'total' => $requirements->total(),
                'from' => $requirements->firstItem(),
                'to' => $requirements->lastItem(),
            ],
            'links' => [
                'first' => $requirements->url(1),
                'last' => $requirements->url($requirements->lastPage()),
                'prev' => $requirements->previousPageUrl(),
                'next' => $requirements->nextPageUrl(),
            ],
        ]);
    }
}
