<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ChecksPermissions
{
    /**
     * Check if the authenticated user has the given permission.
     *
     * Returns a 403 JsonResponse if the user does not have permission.
     * Returns null when the user is authorized.
     *
     * Usage:
     *   if ($error = $this->requirePermission(
     *       $request,
     *       Permission::MANAGE_STAFF
     *   )) {
     *       return $error;
     *   }
     */
    protected function requirePermission(
        Request $request,
        Permission $permission
    ): ?JsonResponse {
        if (!$request->user()->canDo($permission->value)) {
            return response()->json([
                'message'    => 'Forbidden. You do not have permission to perform this action.',
                'permission' => $permission->value,
            ], 403);
        }

        return null;
    }
}