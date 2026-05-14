<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    
 protected $fillable = [
        'organization_id', 'name', 'code', 'description',
        'days_per_year', 'is_paid', 'requires_document', 'is_active',
    ];

    protected $casts=[
'is_paid'=>'boolean',
'requires_document'=>'boolean',
'is_active'=>'boolean',
    ];

    public function organization(): BelongsTo
    {
return $this->belongsTo(Organization::class);
    }
}
