<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $permissions = [
            'check_in',
            'check_out',
            'apply_leave',
            'approve_leave',
            'reject_leave',
            'view_all_attendance',
            'edit_attendance',
            'manage_users',
            'assign_roles',
            'view_reports',
            'manage_policies',
        ];
         foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['slug' => $perm], // unique identifier
                [
                    'name' => ucwords(str_replace('_', ' ', $perm))
                ]
            );
        }
    }
}
