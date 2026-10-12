<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Hapus permission lama yang tidak digunakan oleh route, controller, atau frontend.
     */
    public function up(): void
    {
        $unusedPermissions = [
            'manage rules',
            'view dashboard',
        ];

        Permission::where('guard_name', 'web')
            ->whereIn('name', $unusedPermissions)
            ->delete();
    }

    /**
     * Permission lama tidak dibuat kembali saat rollback karena bukan bagian dari sistem aktif.
     */
    public function down(): void
    {
        // Tidak ada permission aktif yang perlu dipulihkan.
    }
};
