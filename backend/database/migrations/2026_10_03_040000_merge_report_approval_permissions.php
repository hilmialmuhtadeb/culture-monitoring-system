<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $legacyPermissions = ['approve laporan', 'reject laporan'];

    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $permissionColumn = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $approvalPermission = Permission::firstOrCreate([
            'name' => 'approval laporan',
            'guard_name' => 'web',
        ]);
        $legacyPermissions = Permission::where('guard_name', 'web')
            ->whereIn('name', $this->legacyPermissions)
            ->get();

        if ($legacyPermissions->isNotEmpty()) {
            $legacyIds = $legacyPermissions->modelKeys();

            foreach (['role_has_permissions', 'model_has_permissions'] as $tableKey) {
                $table = $tableNames[$tableKey];

                foreach (DB::table($table)->whereIn($permissionColumn, $legacyIds)->get() as $assignment) {
                    $values = (array) $assignment;
                    $values[$permissionColumn] = $approvalPermission->getKey();
                    DB::table($table)->insertOrIgnore($values);
                }
            }

            foreach ($legacyPermissions as $permission) {
                $permission->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $permissionColumn = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $approvalPermission = Permission::where('name', 'approval laporan')
            ->where('guard_name', 'web')
            ->first();

        if ($approvalPermission) {
            $legacyPermissions = [];

            foreach ($this->legacyPermissions as $name) {
                $legacyPermissions[] = Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }

            foreach (['role_has_permissions', 'model_has_permissions'] as $tableKey) {
                $table = $tableNames[$tableKey];

                foreach (DB::table($table)->where($permissionColumn, $approvalPermission->getKey())->get() as $assignment) {
                    foreach ($legacyPermissions as $permission) {
                        $values = (array) $assignment;
                        $values[$permissionColumn] = $permission->getKey();
                        DB::table($table)->insertOrIgnore($values);
                    }
                }
            }

            $approvalPermission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};