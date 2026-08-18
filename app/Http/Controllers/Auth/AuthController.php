<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterOwnerRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\OrgCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    private OrgCodeService $orgCodeService;

    public function __construct(OrgCodeService $orgCodeService)
    {
        $this->orgCodeService = $orgCodeService;
    }

    public function registerOwner(RegisterOwnerRequest $request): JsonResponse
    {

        $orgCode = $this->orgCodeService->generate($request->organization_name);

        $organization = Organization::create([
            'name' => $request->organization_name,
            'email' => $request->organization_email,
            'phone' => $request->organization_phone,
            'address' => $request->organization_address,
            'org_code' => $orgCode,
        ]);
    $this->orgCodeService->setupDefaultRoles($organization);

        $owner = User::create([
            'organization_id' => $organization->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'user_type' => 'owner',
            'require_password_reset' => false,
            'first_login' => false,
        ]);

        $token = $owner->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Owner registration successful',
            'data' => ['user' => new UserResource($owner),
                'organization' => new OrganizationResource($organization),
                'token' => $token, ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = Auth::user()->load(['organization', 'role']);

        // Block suspended accounts
        if ($user->employment_status !== 'active') {
            Auth::logout();

            return response()->json([
                'message' => 'Your account has been suspended. Please contact your administrator.',
            ], 403);
        }

        // Block first login / password reset — no token issued
        if ($user->require_password_reset || $user->first_login) {
            Auth::logout();

            return response()->json([
                'message' => 'You must change your password before proceeding.',
                'requires_password_reset' => true,
                'first_login' => $user->first_login,
                // Return email so the frontend can pre-fill and send it
                // with the first-login-reset request
                'email' => $user->email,
            ], 403);
        }

          // All clear — issue token
        $user->tokens()->delete();
        $token = $user->createToken('api_token')->plainTextToken;


        // ── Face setup check ──────────────────────────────────────────────
        // Only employees need face setup; owners and admins are exempt
        $requiresFaceSetup = $user->isEmployee() && is_null($user->face_template);


        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'permissions' => $user->getPermissions(),
                'first_login' => false,
                'requires_face_setup' => $requiresFaceSetup,
                // Frontend flow:
                // if requires_face_setup === true → navigate to /face-setup screen
                // else → navigate to home dashboard
            ]]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $code = rand(100000, 999999);

        PasswordResetCode::updateOrInsert(
            ['email' => $request->email],
            [
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
            ]
        );

        Mail::raw("Your password reset code is: $code,  it will expire in the next 10 minutes", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Password Reset Code');
        });

        return response()->json([
            'message' => 'Reset code sent to your email',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
{
    // Find user first — return clean JSON if not found
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'No account found with this email address.',
        ], 404);
    }

    // Find the reset record
    $reset = PasswordResetCode::where('email', $request->email)
        ->where('code', $request->code)
        ->first();

    if (!$reset) {
        return response()->json([
            'message' => 'Invalid reset code.',
        ], 400);
    }

    // Check expiration
    if ($reset->expires_at < now()) {
        $reset->delete();

        return response()->json([
            'message' => 'Reset code has expired. Please request a new one.',
        ], 400);
    }

    // Prevent resetting to the same password
    if (Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Your new password must be different from your current password.',
            'errors'  => [
                'password' => ['Choose a password you have not used before.'],
            ],
        ], 422);
    }

    // Update password and clear all reset-related flags
    $user->update([
        'password'               => Hash::make($request->password),
        'require_password_reset' => false,
        'first_login'            => false, // clear in case it was a first-login account
    ]);

    // Revoke all existing tokens — force re-login on all devices
    $user->tokens()->delete();

    // Delete the used OTP — cannot be reused
    $reset->delete();

    return response()->json([
        'message' => 'Password reset successful. You can now log in with your new password.',
    ]);
}
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function firstLoginReset(Request $request): JsonResponse
    {
       $user = User::where('email', $request->email)->first();

       if (!$user) {
        return response()->json([
            'message' => 'No account found with this email address.',
        ], 404);
    }

    // Confirm this endpoint is actually needed for this account
    if (!$user->require_password_reset && !$user->first_login) {
        return response()->json([
            'message' => 'Password reset is not required for this account.',
        ], 422);
    }

    // Verify they actually have the temp password
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'message' => 'The temporary password you entered is incorrect.',
            'errors'  => [
                'current_password' => ['The temporary password is incorrect.'],
            ],
        ], 422);
    }

     // New password must differ from temp password
    if (Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Your new password must be different from the temporary password.',
            'errors'  => [
                'password' => ['Choose a password different from your temporary one.'],
            ],
        ], 422);
    }

    // Update password and clear flags
    $user->update([
        'password'               => Hash::make($request->password),
        'require_password_reset' => false,
        'first_login'            => false,
    ]);

    // Wipe any stale tokens
    $user->tokens()->delete();



        return response()->json([
        'message' => 'Password updated successfully. Please log in with your new password.',
        'data'    => [
            'requires_password_reset' => false,
            'first_login'             => false,
            // No token returned — frontend redirects back to login screen
        ],
    ]);

    }
}
