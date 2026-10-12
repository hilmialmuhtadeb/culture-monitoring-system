<?php

use App\Models\Divisi;
use App\Models\FokusArea;
use App\Models\Laporan;
use App\Models\Permission;
use App\Models\ProgramBudaya;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('all deletable records are archived and unique active names can be reused', function () {
    $user = User::factory()->create();
    $divisi = Divisi::create([
        'nama_divisi' => 'Arsip Divisi',
        'status' => true,
    ]);
    $program = ProgramBudaya::create([
        'nama' => 'Arsip Program',
        'deskripsi' => 'Program untuk pengujian soft delete.',
        'frekuensi' => 1,
        'tahun' => 2026,
        'status' => 'Aktif',
        'approval_document' => 'approval/test.pdf',
        'created_by' => $user->id,
    ]);
    $fokusArea = FokusArea::create(['nama' => 'Arsip Fokus']);
    $laporan = Laporan::create([
        'program_budaya_id' => $program->id,
        'user_id' => $user->id,
        'fokus_area_id' => $fokusArea->id,
        'nama' => 'Arsip Laporan',
        'alasan' => 'Pengujian arsip.',
    ]);
    $role = Role::create([
        'name' => 'Arsip Role',
        'guard_name' => 'web',
    ]);
    $permission = Permission::create([
        'name' => 'arsip permission',
        'guard_name' => 'web',
    ]);

    foreach ([$user, $divisi, $program, $laporan, $role, $permission] as $record) {
        $record->delete();

        expect($record->trashed())->toBeTrue()
            ->and($record::query()->find($record->getKey()))->toBeNull();
    }

    expect(User::factory()->create(['email' => $user->email]))->not->toBeNull()
        ->and(Role::create(['name' => 'Arsip Role', 'guard_name' => 'web']))->not->toBeNull()
        ->and(Permission::create(['name' => 'arsip permission', 'guard_name' => 'web']))->not->toBeNull();
});

test('legacy program progress status is normalized to active', function () {
    $user = User::factory()->create();
    $program = ProgramBudaya::create([
        'nama' => 'Program Progress Lama',
        'deskripsi' => 'Record untuk normalisasi status.',
        'frekuensi' => 1,
        'tahun' => 2026,
        'status' => 'On Progress',
        'approval_document' => 'approval/test.pdf',
        'created_by' => $user->id,
    ]);

    $migration = require database_path(
        'migrations/2026_10_05_000000_normalize_program_budaya_statuses.php'
    );
    $migration->up();

    expect($program->fresh()->status)->toBe('Aktif');
});