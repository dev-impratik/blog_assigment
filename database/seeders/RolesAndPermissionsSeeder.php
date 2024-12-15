<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Core permissions
        $corePermissions = [
            'manage users',
            'manage roles',
            'manage permissions',
            'manage content',
            'view reports',
            'system settings',
        ];

        // Blog management permissions
        $blogPermissions = [
            'view blog',
            'edit blog',
            'delete blog',
            'create blog',
        ];

        // Create or update permissions
        foreach (array_merge($corePermissions, $blogPermissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create or update admin role and assign all permissions
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all()); // Assign all permissions to admin

        // Create or update user role and assign blog permissions
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->syncPermissions($blogPermissions); // Assign only blog-related permissions to user
    }
}
