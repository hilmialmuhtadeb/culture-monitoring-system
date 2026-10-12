<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view inactive program budaya',
            'view own laporan',
            'view all laporan',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            'Admin' => ['view all laporan'],
            'Tim Budaya' => [
                'view inactive program budaya',
                'view all laporan',
            ],
            'CA' => ['view own laporan'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role) {
                $role->givePermissionTo($permissionNames);
            }
        }
    }

    public function down(): void
    {
        $permissionNames = [
            'view inactive program budaya',
            'view own laporan',
            'view all laporan',
        ];

        Permission::where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->delete();
    }
};