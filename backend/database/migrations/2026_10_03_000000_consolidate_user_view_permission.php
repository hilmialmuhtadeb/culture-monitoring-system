<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $permissionColumn = $columnNames['permission_pivot_key'] ?? 'permission_id';

        $legacyPermission = Permission::where('name', 'view user')
            ->where('guard_name', 'web')
            ->first();

        if (!$legacyPermission) {
            return;
        }

        $managePermission = Permission::firstOrCreate([
            'name' => 'manage user',
            'guard_name' => 'web',
        ]);

        foreach (['role_has_permissions', 'model_has_permissions'] as $tableKey) {
            $table = $tableNames[$tableKey];

            foreach (DB::table($table)->where($permissionColumn, $legacyPermission->getKey())->get() as $assignment) {
                $values = (array) $assignment;
                $values[$permissionColumn] = $managePermission->getKey();

                DB::table($table)->insertOrIgnore($values);
            }
        }

        $legacyPermission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};