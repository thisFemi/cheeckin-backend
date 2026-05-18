<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'slug',
    ];

    // protected $casts = [
    //     'permissions' => 'array',
    // ];
    
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }


    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')->withPivot('allowed');
    }

     public function hasPermission(string $slug): bool
    {
        return $this->permissions
            ->where('slug', $slug)
            ->where('pivot.allowed', true)
            ->isNotEmpty();
    }
}
