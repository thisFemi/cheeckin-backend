<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CreateRoleRequest;
use App\Http\Requests\Owner\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    
public function index(Request $request): JsonResponse
    {
        $roles = Role::where('organization_id', $request->user()->organization_id)
            ->withCount('users')   // how many users have this role
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Roles retrieved successfully',
            'data' => [
                'roles' => RoleResource::collection($roles),
            ],
        ]);
    }

     public function store(CreateRoleRequest $request): JsonResponse
    {
    
        $slug = Str::slug($request->name);
        $orgId = $request->user()->organization_id;


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
            ->mapWithKeys(fn($id) => [$id => ['allowed' => true]]);

        $role->permissions()->sync($permissionIds);
    }

    return response()->json([
        'message' => 'Role created.',
        'role'    => new RoleResource($role->load('permissions')),
    ], 201);
    }

    // public function showTest(Request $request): JsonResponse
    // {
  

    //     return response()->json([
    //          'message' => 'Role retrieved successfully',
    //         'data' => [
    //              'role' => "Testing",
    //             ]]);}

      public function show(Request $request, int $id): JsonResponse
    {
        $role = Role::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->withCount('users')
            ->firstOrFail();

        return response()->json([
            'role' => new RoleResource($role->load('permissions')),
        ]);
    }

    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
     $orgId = $request->user()->organization_id;
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


        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => [
                'role' => new RoleResource($role),
            ],
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
{
    $role = Role::where('id', $id)
        ->where('organization_id', $request->user()->organization_id)
        ->firstOrFail();

    // Prevent deletion if users are assigned
    if ($role->users()->exists()) {
        return response()->json([
            'message' => 'Cannot delete. Users are assigned to this role. Reassign them first.',
        ], 422);
    }

    // Pivot will auto-delete if cascade is set
    $role->delete();

    return response()->json([
        'message' => 'Role deleted.',
    ]);
}
}
