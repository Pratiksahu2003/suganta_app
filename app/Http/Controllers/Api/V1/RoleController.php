<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseApiController
{
    /**
     * Get list of all roles (excludes admin and student).
     */
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->whereNotIn('name', ['admin', 'student'])
            ->orderBy('name')
            ->get(['id', 'name', 'display_name']);

        return $this->success('Roles retrieved successfully.', $roles);
    }
}
