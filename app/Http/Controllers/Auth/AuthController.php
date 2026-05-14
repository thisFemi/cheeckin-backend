<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterEmployeeRequest;
use App\Http\Requests\Auth\RegisterOwnerRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\OrgCodeService;
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


public function registerOwner(RegisterOwnerRequest $request):JsonResponse{

   $orgCode=$this->orgCodeService->generate($request->organization_name);

    $organization = Organization::create([
        'name' => $request->organization_name,
        'email' => $request->organization_email,
        'phone' => $request->organization_phone,
        'address' => $request->organization_address,
        'org_code' => $orgCode,
    ]);

    $owner = User::create([
        'organization_id'=>$organization->id,
        'first_name'    =>$request->first_name,
        'last_name'     =>$request->last_name,
        'email'         =>$request->email,
        'password'      =>Hash::make($request->password),
        'phone'         =>$request->phone,
        'user_type'     =>'owner',
    ]);

    $token =$owner->createToken('auth_token')->plainTextToken;  
     return response()->json([
        'message'       => 'Owner registration successful',
          'data'=>[  'user' => new UserResource($owner),
            'organization' => new OrganizationResource($organization),
            'token'        => $token,]
        ], 201);
}


public function registerEmployee(RegisterEmployeeRequest $request):JsonResponse{
    $organization=Organization::where('org_code',$request->org_code)
    ->where('is_active',true)
    ->firstOrFail();

    $employee = User::create([
        'organization_id'=>$organization->id,
        'first_name'    =>$request->first_name,
        'last_name'     =>$request->last_name,
        'email'         =>$request->email,
        'password'      =>Hash::make($request->password),
        'phone'         =>$request->phone,
        'user_type'     =>'employee',
        'employee_id'  => $request->employee_id,
        'department'   => $request->department,
        'position'     => $request->position,
        'joined_date'  => $request->joined_date,
        'face_template'  => null, 

    ]);  
    $token = $employee->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'             => 'Employee registration successful',
            'data'=>[
            'user'                => new UserResource($employee->load('organization')),
            'token'               => $token,
            'requires_face_setup' => true, // Frontend should redirect to face setup screen
            ]
        ], 201);
    }

     public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

       $user = Auth::user()->load(['organization', 'role']);

        // Reject suspended or inactive accounts
        if ($user->employment_status !== 'active') {
            Auth::logout();
            return response()->json([
                'message' => 'Your account has been suspended. Please contact your administrator.',
            ], 403);
        }

        // Revoke previous tokens (single device session — optional)
        $user->tokens()->delete();

        $token = $user->createToken('api_token')->plainTextToken;

        // ── Face setup check ──────────────────────────────────────────────
        // Only employees need face setup; owners and admins are exempt
        $requiresFaceSetup = $user->isEmployee() && is_null($user->face_template);

        return response()->json([
            'message' => 'Login successful',
                'data'=>[
            'user'                => new UserResource($user),
            'token'               => $token,
            'requires_face_setup' => $requiresFaceSetup,
            // Frontend flow:
            // if requires_face_setup === true → navigate to /face-setup screen
            // else → navigate to home dashboard
        ]]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
       {
       $code =rand(100000,999999); 

       PasswordResetCode::updateOrInsert(
        ['email' => $request->email],
        [
            'code' => $code, 
            'expires_at' => now()->addMinutes(10)
            ]
        );

   Mail::raw("Your password reset code is: $code,  it will expire in the next 10 minutes", function ($message) use ($request) {
        $message->to($request->email)
                ->subject('Password Reset Code');
    });

    return response()->json([
        'message' => 'Reset code sent to your email'
    ]); }


    public function resetPassword(ResetPasswordRequest $request): JsonResponse
{
    // Find the reset record
    $reset = PasswordResetCode::where('email', $request->email)
                ->where('code', $request->code)
                ->first();

    if (!$reset) {
        return response()->json([
            'message' => 'Invalid reset code'
        ], 400);
    }

    // Check expiration
    if ($reset->expires_at < now()) {
        return response()->json([
            'message' => 'Reset code has expired'
        ], 400);
    }

    // Update user password
    $user = User::where('email', $request->email)->first();
    $user->password = Hash::make($request->password);
    $user->save();

    // Delete used code (important)
    $reset->delete();

    return response()->json([
        'message' => 'Password reset successful, you can now log in with your new password'
    ]);
}
    
  public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
    }