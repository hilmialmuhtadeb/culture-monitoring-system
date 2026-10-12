<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user manager can load role and division options without role or division management permissions', function () {
    $permission = Permission::firstOrCreate([
        'name' => 'manage user',
        'guard_name' => 'web',
    ]);
    $role = Role::create([
        'name' => 'User Manager',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permission);
    $user = User::factory()->create();
    $user->assignRole($role);
    $archivedUser = User::factory()->create([
        'email' => 'reusable.user@example.test',
    ]);
    $archivedUser->delete();

    $this->actingAs($user)
        ->getJson('/api/roles')
        ->assertOk();

    $this->actingAs($user)
        ->getJson('/api/divisi')
        ->assertOk();

    $this->actingAs($user)
        ->postJson('/api/roles', [
            'name' => 'Not Allowed',
            'permissions' => [],
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson('/api/users', [
            'name' => 'Pengguna Baru',
            'email' => $archivedUser->email,
            'role' => $role->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', $archivedUser->email);
});