<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
   public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'users_count' => $this->when(
                isset($this->users_count),
                $this->users_count
            ),
            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                fn() => $this->permissions->map(fn($p) => [
                    'id'      => $p->id,
                    'name'    => $p->name,
                    'slug'    => $p->slug,
                    'group'   => $p->group,
                    'allowed' => $p->pivot->allowed,
                ])
            ),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
