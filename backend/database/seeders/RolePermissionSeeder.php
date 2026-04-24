<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'create_bug', 'edit_bug', 
            'change_status_in_progress_fixed', 'change_status_closed_reopened',
            'add_comments', 'add_internal_notes', 'assign_devs',
            'manage_taxonomies', // Projects, Tags, Categories
            'manage_users', // Invite users, change roles
            'view_admin_dashboard', 'view_dev_dashboard', 'view_service_dashboard'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $roles = [
            'Admin' => [
                'create_bug', 'edit_bug', 'change_status_in_progress_fixed', 'change_status_closed_reopened',
                'add_comments', 'add_internal_notes', 'assign_devs',
                'manage_taxonomies', 'manage_users', 'view_admin_dashboard', 'view_dev_dashboard', 'view_service_dashboard'
            ],
            'Developer' => [
                'create_bug', 'edit_bug', 'change_status_in_progress_fixed',
                'add_comments', 'add_internal_notes', 'assign_devs',
                'view_dev_dashboard'
            ],
            'Tester' => [
                'create_bug', 'edit_bug', 'change_status_closed_reopened',
                'add_comments'
            ],
            'Service' => [
                'create_bug', 'add_comments', 'view_service_dashboard'
            ]
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $permissionIds = Permission::whereIn('name', $rolePermissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
