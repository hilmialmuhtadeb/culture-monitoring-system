<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legacy CRUD grants are consolidated without changing report deletion access', function () {
    $legacyNames = [
        'create laporan',
        'update laporan',
        'create user',
        'update user',
        'delete user',
        'create program budaya',
        'update program budaya',
        'delete program budaya',
    ];

    $role = Role::create([
        'name' => 'Legacy Manager',
        'guard_name' => 'web',
    ]);

    foreach ($legacyNames as $name) {
        $role->givePermissionTo(Permission::create([
            'name' => $name,
            'guard_name' => 'web',
        ]));
    }

    $deleteReportsRole = Role::create([
        'name' => 'Report Deleter',
        'guard_name' => 'web',
    ]);
    $deleteReportsRole->givePermissionTo(Permission::create([
        'name' => 'delete laporan',
        'guard_name' => 'web',
    ]));

    $directGrantUser = User::factory()->create();
    $directGrantUser->givePermissionTo(Permission::findByName('update laporan'));

    $migration = require database_path(
        'migrations/2026_10_02_000000_consolidate_crud_permissions.php'
    );
    $migration->up();

    expect($role->fresh()->hasPermissionTo('manage laporan'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('manage user'))->toBeTrue()
        ->and($role->fresh()->hasPermissionTo('manage program budaya'))->toBeTrue()
        ->and($directGrantUser->fresh()->hasPermissionTo('manage laporan'))->toBeTrue()
        ->and($deleteReportsRole->fresh()->hasPermissionTo('delete laporan'))->toBeTrue()
        ->and($deleteReportsRole->fresh()->hasPermissionTo('manage laporan'))->toBeFalse()
        ->and(Permission::whereIn('name', $legacyNames)->exists())->toBeFalse();
});

test('legacy view user grants are consolidated into manage user', function () {
    $role = Role::create([
        'name' => 'User Viewer',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo(Permission::create([
        'name' => 'view user',
        'guard_name' => 'web',
    ]));

    $migration = require database_path(
        'migrations/2026_10_03_000000_consolidate_user_view_permission.php'
    );
    $migration->up();

    expect($role->fresh()->hasPermissionTo('manage user'))->toBeTrue()
        ->and(Permission::where('name', 'view user')->exists())->toBeFalse();
});

test('legacy approve and reject grants are consolidated into approval laporan', function () {
    $role = Role::create([
        'name' => 'Report Reviewer',
        'guard_name' => 'web',
    ]);

    foreach (['approve laporan', 'reject laporan'] as $name) {
        $role->givePermissionTo(Permission::create([
            'name' => $name,
            'guard_name' => 'web',
        ]));
    }

    $migration = require database_path(
        'migrations/2026_10_03_040000_merge_report_approval_permissions.php'
    );
    $migration->up();

    expect($role->fresh()->hasPermissionTo('approval laporan'))->toBeTrue()
        ->and(Permission::whereIn('name', ['approve laporan', 'reject laporan'])->exists())->toBeFalse();
});