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
            'manage_staff' => 'staff',

            'check_in_out' => 'attendance',
            'view_all_attendance' => 'attendance',
            'edit_attendance' => 'attendance',

            'apply_leave' => 'leave',
            'manage_leave' => 'leave',
            'approve_or_reject_leave' => 'leave',

            'manage_policies' => 'policies',

            'manage_roles' => 'roles',

            'view_reports' => 'reports',
        ];

        foreach (PermissionEnum::list() as $slug => $name) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'group' => $groups[$slug] ?? null,
                ]
            );
        }

        $this->command->info('Permissions seeded: '.count(PermissionEnum::list()));
    }
}
