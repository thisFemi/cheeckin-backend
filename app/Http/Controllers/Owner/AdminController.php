<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CreateAdminRequest;
use App\Http\Requests\Owner\UpdateAdminRequest;
use App\Http\Resources\UserResource;
use App\Models\User;

use App\Notifications\StaffWelcomeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

    public function store(CreateAdminRequest $request): JsonResponse{
        $owner = $request->user();
        $tempPassword = Str::random(10);

        $admin = User::create([
              'organization_id'   => $owner->organization_id,
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'email'             => $request->email,
            'password'          => Hash::make($tempPassword),
            'phone'             => $request->phone,
            'department'        => $request->department,
            'position'          => $request->position,
            'role_id'           => $request->role_id,
            'employee_id'       => $request->employee_id,
            'joined_date'       => $request->joined_date,
            'user_type'         => 'admin',           // hardcoded — cannot be changed by request
            'employment_status' => 'active',
            'face_template'     => null,
        ]);

         // Send welcome email with temp password
        $admin->notify(new StaffWelcomeNotification($owner->organization, $tempPassword));

        return response()->json([
            'message' => 'Admin created successfully',
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

    public function update(UpdateAdminRequest $request, int $id): JsonResponse
    {
        $admin = User::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->where('user_type', 'admin')
            ->firstOrFail();
            

        $admin->update($request->validated());

        return response()->json([
            'message' => 'Admin updated successfully.',
            'data' => [
                 'admin' => new UserResource($admin->fresh('role')),
                ]]);
           
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
