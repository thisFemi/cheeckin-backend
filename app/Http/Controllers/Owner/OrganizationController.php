<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Services\OrgCodeService;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class OrganizationController extends Controller
{
    public function __construct(private OrgCodeService $orgCodeService)
  {}
  
  public function show(Request $request)
  {
    $organization = $request->user()
    ->organization()
    ->with(['owner', 'admins','employees'])
    ->withCount(['admins', 'employees'])
    ->first();

    return response()->json([
        'message'       => 'Successful',
          'data'=>[ 
            'organization' => new OrganizationResource($organization),
            ]
        ], 201);
  }

        public function update(UpdateOrganizationRequest $request):JsonResponse{
        $organization = $request->user()->organization;
        $data = $request->validated();

        if($request->hasFile('logo')){
        if($organization->logo){
            Storage::disk('public')->delete($organization->logo);   
        }
        $data['logo']= $request->file('logo')->store('logos', 'public');
        $organization->update($data);
        }
        return response()->json([
            'message'       => 'Organization updated successfully',
            'data'=>[ 
                'organization' => new OrganizationResource($organization),
                ]
            ], 200);}

        public function orgCode(): JsonResponse
        {
            $organization = auth()->user()->organization;

            if (!$organization) {
                return response()->json([
                    'message' => 'User is not assigned to any organization'
                ], 404);
            }

            return response()->json([
                'message' => 'Organization code retrieved successfully',
                'data' => [
                    'org_code' => $organization->org_code,
                ]
            ]);
        }

        public function regenerateOrgCode(): JsonResponse
        {
            $organization = auth()->user()->organization;

            if (!$organization) {
                return response()->json([
                    'message' => 'User is not assigned to any organization'
                ], 404);
            }

            $newOrgCode = $this->orgCodeService->generate($organization->name);
            $organization->org_code = $newOrgCode;
            $organization->save();

            return response()->json([
                'message' => 'Organization code regenerated successfully',
                'data' => [
                    'org_code' => $newOrgCode,
                ]
            ]);
        }
  }