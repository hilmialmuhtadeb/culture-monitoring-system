<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'program_budayas', 'laporans', 'divisis'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->softDeletes());
            }
        }

        $rolesTable = config('permission.table_names.roles');
        $permissionsTable = config('permission.table_names.permissions');

        foreach ([$rolesTable, $permissionsTable] as $tableName) {
            if (!Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->softDeletes());
            }
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique');
            DB::statement("ALTER TABLE {$rolesTable} DROP CONSTRAINT IF EXISTS roles_name_guard_name_unique");
            DB::statement("ALTER TABLE {$permissionsTable} DROP CONSTRAINT IF EXISTS permissions_name_guard_name_unique");
        } else {
            DB::statement('DROP INDEX IF EXISTS users_email_unique');
            DB::statement('DROP INDEX IF EXISTS roles_name_guard_name_unique');
            DB::statement('DROP INDEX IF EXISTS permissions_name_guard_name_unique');
        }

        DB::statement('CREATE UNIQUE INDEX users_email_active_unique ON users (email) WHERE deleted_at IS NULL');
        DB::statement("CREATE UNIQUE INDEX roles_name_guard_active_unique ON {$rolesTable} (name, guard_name) WHERE deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX permissions_name_guard_active_unique ON {$permissionsTable} (name, guard_name) WHERE deleted_at IS NULL");
    }

    public function down(): void
    {
        $rolesTable = config('permission.table_names.roles');
        $permissionsTable = config('permission.table_names.permissions');

        DB::statement('DROP INDEX IF EXISTS users_email_active_unique');
        DB::statement('DROP INDEX IF EXISTS roles_name_guard_active_unique');
        DB::statement('DROP INDEX IF EXISTS permissions_name_guard_active_unique');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email)');
            DB::statement("ALTER TABLE {$rolesTable} ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name)");
            DB::statement("ALTER TABLE {$permissionsTable} ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name)");
        } else {
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
            DB::statement("CREATE UNIQUE INDEX roles_name_guard_name_unique ON {$rolesTable} (name, guard_name)");
            DB::statement("CREATE UNIQUE INDEX permissions_name_guard_name_unique ON {$permissionsTable} (name, guard_name)");
        }

        Schema::table($permissionsTable, fn (Blueprint $table) => $table->dropSoftDeletes());
        Schema::table($rolesTable, fn (Blueprint $table) => $table->dropSoftDeletes());

        foreach (['divisis', 'laporans', 'program_budayas', 'users'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropSoftDeletes());
        }
    }
};