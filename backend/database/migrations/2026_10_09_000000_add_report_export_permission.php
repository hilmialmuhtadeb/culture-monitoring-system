<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'export laporan',
            'guard_name' => 'web',
        ]);

        $role = Role::where('name', 'Tim Budaya')
            ->where('guard_name', 'web')
            ->first();

        $role?->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'export laporan')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};