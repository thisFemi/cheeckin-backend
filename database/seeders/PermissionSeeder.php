<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'manage_staff'        => 'staff',
            'view_staff'          => 'staff',
            'manage_attendance'   => 'attendance',
            'view_attendance'     => 'attendance',
            'override_attendance' => 'attendance',
            'manage_leave_types'  => 'leave',
            'approve_leave'       => 'leave',
            'view_leave'          => 'leave',
            'manage_policies'     => 'policies',
            'manage_roles'        => 'roles',
            'view_reports'        => 'reports',
        ];

        foreach (PermissionEnum::list() as $slug => $name) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'  => $name,
                    'group' => $groups[$slug] ?? null,
                ]
            );
        }

        $this->command->info('Permissions seeded: ' . count(PermissionEnum::list()));
    }
}