<?php
namespace App\Services;
use App\Models\Organization;
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
}