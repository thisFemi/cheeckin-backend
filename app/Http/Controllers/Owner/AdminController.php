<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CreateAdminRequest;
use App\Http\Requests\Owner\UpdateAdminRequest;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Notifications\StaffWelcomeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $admins = User::where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'admin')
            ->with('role')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Admins retrieved successfully',
            'data' => [
                'admins' => UserResource::collection($admins),

            ]]);
    }

    public function store(CreateAdminRequest $request): JsonResponse
    {
        $owner = $request->user();
        $tempPassword = Str::random(10);

        $admin = User::create([
            'organization_id' => $owner->organization_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($tempPassword),
            'phone' => $request->phone,
            'department' => $request->department,
            'position' => $request->position,
            'role_id' => $request->role_id, // 👈 assign role at creation
            'user_type' => 'admin',
            'employment_status' => 'active',
            'face_template' => null,
            'require_password_reset' => true,
            'first_login' => true,
        ]);

        // Send welcome email with temp password
         $organization = Organization::find($owner->organization_id);
        $admin->notify(new StaffWelcomeNotification($organization, $tempPassword));

        return response()->json([
            'message' => 'Admin account created. Welcome email sent.',
            'data' => [
                'admin' => new UserResource($admin->load('role')),
            ]]);

    }

    public function show(Request $request, int $id): JsonResponse
    {
        $admin = User::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'admin')
            ->with('role')
            ->firstOrFail();

        return response()->json([
            'message' => 'Admin retrieved successfully',
            'data' => [
                'admin' => new UserResource($admin),
            ]]);

    }

    // POST /api/owner/admins/{id}/assign-role
    public function assignRole(Request $request, int $id): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')
                    ->where('organization_id', $request->user()->organization_id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $admin = User::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'admin')
            ->firstOrFail();

        if ($admin->role_id === $request->role_id) {
            return response()->json([
                'message' => 'This admin is already assigned to this role.',
            ], 422);
        }
        $previousRole = $admin->role?->name;
        $admin->update(['role_id' => $request->role_id]);
        $newRole = Role::with('permissions')->find($request->role_id);

        return response()->json([
            'message' => "Role assigned to {$admin->first_name} {$admin->last_name}.",
            'data' => [
                'previous_role' => $previousRole ?? 'None',
                'new_role' => [
                    'id' => $newRole->id,
                    'name' => $newRole->name,
                    'permissions' => $newRole->permissions
                        ->where('pivot.allowed', true)
                        ->pluck('slug'),
                ],
            ],
        ]);

    }

    // DELETE /api/owner/admins/{id}/deassign-role
    public function deassignRole(Request $request, int $id): JsonResponse
    {
        $admin = User::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'admin')
            ->firstOrFail();

        if (! $admin->role_id) {
            return response()->json([
                'message' => 'This admin has no role assigned.',
            ], 422);
        }

        $previousRole = $admin->role->name;
        $admin->update(['role_id' => null]);

        return response()->json([
            'message' => "Role '{$previousRole}' removed from {$admin->first_name} {$admin->last_name}.",
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $admin = User::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'admin')
            ->firstOrFail();

        // Prevent owner from deleting themselves
        if ($admin->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        // Revoke all tokens — force logout
        $admin->tokens()->delete();

        // Soft delete
        $admin->update(['employment_status' => 'inactive']);
        $admin->delete();

        return response()->json([
            'message' => 'Admin account removed.',
        ]);
    }
}
