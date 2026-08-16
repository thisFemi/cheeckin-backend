<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CreateRoleRequest;
use App\Http\Requests\Owner\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roles = Role::where('organization_id', $request->user()->organization_id)
            ->withCount('users')
            ->with('permissions')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Roles retrieved successfully.',
            'data' => [
                'roles' => RoleResource::collection($roles),
            ],
        ]);
    }

    public function store(CreateRoleRequest $request): JsonResponse
    {
         $user = $request->user();
        if( !$user->canDo('assign_roles')){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $slug = Str::slug($request->name);
        $orgId = $user->organization_id;

        // Ensure slug is unique within this org
        $exists = Role::where('organization_id', $orgId)
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A role with this name already exists in your organization.',
            ], 422);
        }

        $role = Role::create([
            'organization_id' => $orgId,
            'name' => $request->name,
            'slug' => $slug,
        ]);

        if ($request->filled('permissions')) {
            $permissionIds = Permission::whereIn('slug', $request->permissions)
                ->pluck('id')
                ->mapWithKeys(fn ($id) => [$id => ['allowed' => true]]);

            $role->permissions()->sync($permissionIds);
        }

        return response()->json([
            'message' => 'Role created.',
            'role' => new RoleResource($role->load('permissions')),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $role = Role::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->withCount('users')
            ->with('permissions')
            ->firstOrFail();

        return response()->json([
            'message' => 'Role retrieved successfully.',
            'data' => [
                'role' => new RoleResource($role),
            ],
        ]);
    }

    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
         $user = $request->user();
        if( !$user->canDo('assign_roles')){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $orgId = $user->organization_id;

        $role = Role::where('id', $id)
            ->where('organization_id', $orgId)
            ->firstOrFail();

        $slug = Str::slug($request->name);

        $exists = Role::where('organization_id', $orgId)
            ->where('slug', $slug)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A role with this name already exists in your organization.',
            ], 422);
        }

        $role->update([
            'name' => $request->name,
            'slug' => $slug,
        ]);

          if ($request->filled('permissions')) {
            $permissionIds = Permission::whereIn('slug', $request->permissions)
                ->pluck('id')
                ->mapWithKeys(fn($id) => [$id => ['allowed' => true]]);

            $role->permissions()->sync($permissionIds);
        }

        if ($request->has('permissions') && empty($request->permissions)) {
            $role->permissions()->detach();
        }

        return response()->json([
            'message' => 'Role updated successfully.',
            'data'    => [
                'role' => new RoleResource($role->fresh('permissions')),
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
         $user = $request->user();
        if( !$user->canDo('assign_roles')){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $role = Role::where('id', $id)
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        // Prevent deletion if users are assigned
        if ($role->users()->exists()) {
            $count = $role->users()->count();
            return response()->json([
                'message' => "Cannot delete. {$count} user(s) are assigned to this role. Reassign them first.",
            ], 422);
        }

        // Pivot will auto-delete if cascade is set
         $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'message' => 'Role deleted.',
        ]);
    }

    // GET /api/owner/permissions
    // Returns the full predefined permissions list — used by the frontend
    // when building the role creation/edit form
    public function permissions(): JsonResponse
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        // Group by category for easier frontend rendering
        $grouped = $permissions->groupBy('group')->map(function ($items, $group) {
            return [
                'group'       => $group,
                'permissions' => $items->map(fn($p) => [
                    'id'   => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                ]),
            ];
        })->values();

        return response()->json([
            'message' => 'Permissions retrieved successfully.',
            'data'    => [
                'permissions' => $grouped,
            ],
        ]);
    }
    
    // POST /api/owner/staff/{id}/assign-role
    public function assignRole(Request $request, int $id): JsonResponse
    {
         $user = $request->user();
        if( !$user->canDo('assign_roles')){
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $validator = Validator::make($request->all(), [
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')
                    ->where('organization_id', $user->organization_id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $staff = User::where('id', $id)
            ->where('organization_id', $user->organization_id)
            ->where('user_type', 'employee')
            ->firstOrFail();

        // Check if already has this role
        if ($staff->role_id === $request->role_id) {
            return response()->json([
                'message' => "This staff member is already assigned to this role.",
                'role'    => new RoleResource(Role::find($request->role_id)),
            ], 422);
        }

        $previousRole = $staff->role?->name;

        $staff->update(['role_id' => $request->role_id]);

        $newRole = Role::with('permissions')->find($request->role_id);

        return response()->json([
            'message' => "Role assigned to {$staff->first_name} {$staff->last_name}.",
            'data'    => [
                'staff_id'      => $staff->id,
                'previous_role' => $previousRole ?? 'None',
                'new_role'      => [
                    'id'          => $newRole->id,
                    'name'        => $newRole->name,
                    'permissions' => $newRole->permissions
                        ->where('pivot.allowed', true)
                        ->pluck('slug'),
                ],
            ],
        ]);
    }

    // DELETE /api/owner/staff/{id}/deassign-role
    public function deassignRole(Request $request, int $id): JsonResponse
    {
          $user = $request->user();

        if( !$user->canDo('assign_roles')){
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $staff = User::where('id', $id)
            ->where('organization_id', $user->organization_id)
            ->where('user_type', 'employee')
            ->firstOrFail();

        if (!$staff->role_id) {
            return response()->json([
                'message' => 'This staff member has no role assigned.',
            ], 422);
        }

        $previousRole = $staff->role->name;

        $staff->update(['role_id' => null]);

        return response()->json([
            'message' => "Role '{$previousRole}' removed from {$staff->first_name} {$staff->last_name}.",
            'data'    => [
                'staff_id'      => $staff->id,
                'previous_role' => $previousRole,
                'current_role'  => null,
            ],
        ]);
    }
}
