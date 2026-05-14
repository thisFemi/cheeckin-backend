<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'address'        => $this->address,
            'logo'           => $this->logo
                                    ? Storage::disk('public')->url($this->logo)
                                    : null,
            'org_code'       => $this->org_code,
            'is_active'      => $this->is_active,
            'admins_count'   => $this->when(isset($this->admins_count),  $this->admins_count),
            'employees_count'=> $this->when(isset($this->employees_count), $this->employees_count),
            'created_at'     => $this->created_at->toIso8601String(),
        ];
    }
}
