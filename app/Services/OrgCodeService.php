<?php
namespace App\Services;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
// app/Services/OrgCodeService.php
class OrgCodeService
{
    public function generate(string $orgName): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $orgName), 0, 4));
        $prefix = str_pad($prefix, 4, 'X');

        do {
            $suffix = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4));
            $code   = $prefix . '-' . $suffix;
        } while (
            Organization::where('org_code', $code)->exists()
        );

        return  $code;
    }

    // app/Services/OrgCodeService.php — or create a new OrgSetupService

public function setupDefaultRoles(Organization $organization): Role
{
    // Create Super Admin role with all permissions
    $superAdmin = Role::create([
        'organization_id' => $organization->id,
        'name'            => 'Super Admin',
        'slug'            => 'super-admin',
    ]);

    $allPermissions = Permission::pluck('id')
        ->mapWithKeys(fn($id) => [$id => ['allowed' => true]]);

    $superAdmin->permissions()->sync($allPermissions);

    return $superAdmin;
}
}