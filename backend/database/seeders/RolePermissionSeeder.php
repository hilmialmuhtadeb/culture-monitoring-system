<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache permission Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Program Budaya
            'view program budaya',
            'view inactive program budaya',
            'manage program budaya',

            // Laporan
            'view laporan',
            'view own laporan',
            'view all laporan',
            'manage laporan',
            'delete laporan',
            'approval laporan',
            'export laporan',

            // Master Data - User
            'manage user',

            // Master Data - Divisi
            'manage divisi',

            // Master Data - Roles
            'manage roles',

            // Master Data - Permission
            'manage permission',

            // Materi
            'view materi',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        $timBudaya = Role::firstOrCreate([
            'name' => 'Tim Budaya',
            'guard_name' => 'web',
        ]);

        $ca = Role::firstOrCreate([
            'name' => 'CA',
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        |
        | Admin mengelola User dan seluruh Master Data.
        | Admin hanya melihat Program Budaya yang aktif.
        |
        */

        $admin->syncPermissions([
            'view program budaya',

            'view laporan',
            'view all laporan',

            // User
            'manage user',

            // Master Data
            'manage divisi',
            'manage roles',
            'manage permission',

            'view materi',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Tim Budaya
        |--------------------------------------------------------------------------
        |
        | Tim Budaya mengelola Program Budaya dan memproses laporan CA.
        |
        */

        $timBudaya->syncPermissions([
            'view program budaya',
            'view inactive program budaya',
            'manage program budaya',

            'view laporan',
            'view all laporan',
            'delete laporan',
            'approval laporan',
            'export laporan',

            'view materi',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CA (Change Agent)
        |--------------------------------------------------------------------------
        |
        | CA membuat dan mengelola laporan milik divisinya sendiri.
        |
        */

        $ca->syncPermissions([
            'view program budaya',

            'view laporan',
            'view own laporan',
            'manage laporan',

            'view materi',
        ]);
    }
}