<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clear cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Locate and read the exported JSON file
        $jsonPath = database_path('seeders/roles_permissions_export.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error("Export file not found at: {$jsonPath}");
            return;
        }

        $rolesData = json_decode(file_get_contents($jsonPath), true);

        // 3. Loop through roles and their permissions
        foreach ($rolesData as $roleItem) {
            
            // Create or fetch the role
            $role = Role::firstOrCreate([
                'name' => $roleItem['name'],
                'guard_name' => $roleItem['guard_name'] ?? 'web',
            ]);

            $permissionNames = [];
            
            // Create permissions if missing and collect their names
            foreach ($roleItem['permissions'] as $permissionItem) {
                Permission::firstOrCreate([
                    'name' => $permissionItem['name'],
                    'guard_name' => $permissionItem['guard_name'] ?? 'web',
                ]);
                
                $permissionNames[] = $permissionItem['name'];
            }

            // 4. Sync permissions to this role
            $role->syncPermissions($permissionNames);
        }
        
        $this->command->info('Roles and permissions successfully imported from JSON!');
    }
}
