<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissionGroups = [
        'manage laporan' => [
            'create laporan',
            'update laporan',
        ],
        'manage user' => [
            'create user',
            'update user',
            'delete user',
        ],
        'manage program budaya' => [
            'create program budaya',
            'update program budaya',
            'delete program budaya',
        ],
    ];

    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $permissionColumn = $columnNames['permission_pivot_key'] ?? 'permission_id';

        foreach ($this->permissionGroups as $targetName => $oldNames) {
            $target = Permission::firstOrCreate([
                'name' => $targetName,
                'guard_name' => 'web',
            ]);

            $oldPermissions = Permission::where('guard_name', 'web')
                ->whereIn('name', $oldNames)
                ->get();

            if ($oldPermissions->isEmpty()) {
                continue;
            }

            $oldIds = $oldPermissions->modelKeys();

            $this->copyAssignments(
                $tableNames['role_has_permissions'],
                $permissionColumn,
                $oldIds,
                $target->getKey(),
            );
            $this->copyAssignments(
                $tableNames['model_has_permissions'],
                $permissionColumn,
                $oldIds,
                $target->getKey(),
            );

            foreach ($oldPermissions as $oldPermission) {
                $oldPermission->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $permissionColumn = $columnNames['permission_pivot_key'] ?? 'permission_id';

        foreach ($this->permissionGroups as $targetName => $oldNames) {
            $target = Permission::where('guard_name', 'web')
                ->where('name', $targetName)
                ->first();

            if (!$target) {
                continue;
            }

            foreach ($oldNames as $oldName) {
                $oldPermission = Permission::firstOrCreate([
                    'name' => $oldName,
                    'guard_name' => 'web',
                ]);

                $this->copyAssignments(
                    $tableNames['role_has_permissions'],
                    $permissionColumn,
                    [$target->getKey()],
                    $oldPermission->getKey(),
                );
                $this->copyAssignments(
                    $tableNames['model_has_permissions'],
                    $permissionColumn,
                    [$target->getKey()],
                    $oldPermission->getKey(),
                );
            }

            $target->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function copyAssignments(
        string $table,
        string $permissionColumn,
        array $sourceIds,
        int|string $targetId,
    ): void {
        foreach (DB::table($table)->whereIn($permissionColumn, $sourceIds)->get() as $assignment) {
            $values = (array) $assignment;
            $values[$permissionColumn] = $targetId;

            DB::table($table)->insertOrIgnore($values);
        }
    }
};