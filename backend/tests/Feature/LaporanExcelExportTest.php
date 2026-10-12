<?php

use App\Models\Divisi;
use App\Models\FokusArea;
use App\Models\Laporan;
use App\Models\Permission;
use App\Models\ProgramBudaya;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

test('Tim Budaya can download a template-formatted report export within its visibility scope', function () {
    $exportPermission = Permission::firstOrCreate([
        'name' => 'export laporan',
        'guard_name' => 'web',
    ]);
    $viewAllPermission = Permission::firstOrCreate([
        'name' => 'view all laporan',
        'guard_name' => 'web',
    ]);
    $role = Role::create([
        'name' => 'Export Reviewer',
        'guard_name' => 'web',
    ]);
    $role->syncPermissions([$exportPermission, $viewAllPermission]);

    $division = Divisi::create([
        'nama_divisi' => 'Unit Audit',
        'divisi_alias' => 'AUD',
        'status' => true,
    ]);
    $user = User::factory()->create(['id_divisi' => $division->id]);
    $user->assignRole($role);

    $program = ProgramBudaya::create([
        'nama' => 'Program Keselamatan',
        'deskripsi' => 'Rencana keselamatan kerja.',
        'frekuensi' => 1,
        'tahun' => 2026,
        'status' => 'Aktif',
        'approval_document' => 'approval/policy.pdf',
        'created_by' => $user->id,
    ]);
    $focus = FokusArea::create(['nama' => 'Safety & Wellbeing First']);
    Laporan::create([
        'program_budaya_id' => $program->id,
        'user_id' => $user->id,
        'fokus_area_id' => $focus->id,
        'nama' => 'Simulasi Keselamatan',
        'alasan' => 'Simulasi evakuasi area kerja.',
        'alasan_fokus_area' => 'Mengurangi risiko kecelakaan.',
        'pimpinan_hadir' => ['VP'],
        'tanggal_realisasi' => '2026-10-01',
        'video_url' => 'https://example.test/video',
        'pimpinan_terlibat' => true,
        'change_agent_berpartisipasi' => true,
        'target_divisi_60_90_persen' => false,
        'images' => [],
        'status' => 'Approved',
    ]);

    $response = $this->actingAs($user)->get('/api/laporan/export');
    $response->assertOk();
    $response->assertHeader(
        'content-type',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );

    $temporaryFile = tempnam(sys_get_temp_dir(), 'laporan-export-');
    file_put_contents($temporaryFile, $response->streamedContent());

    try {
        $sheet = IOFactory::load($temporaryFile)->getActiveSheet();

        expect($sheet->getCell('C1')->getValue())->toBe('Tanggal Pelaporan ')
            ->and($sheet->getCell('E1')->getValue())->toBe('Nama Program')
            ->and($sheet->getCell('E2')->getValue())->toBe('Program Keselamatan')
            ->and($sheet->getCell('F2')->getValue())->toBe('Safety & Wellbeing First')
            ->and($sheet->getCell('I2')->getValue())->toBe('Rencana keselamatan kerja.')
            ->and($sheet->getCell('J2')->getValue())->toContain('Simulasi Keselamatan')
            ->and($sheet->getCell('N2')->getValue())->toContain('https://example.test/video');
    } finally {
        @unlink($temporaryFile);
    }
});

test('report export is denied without the export permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/laporan/export')
        ->assertForbidden();
});