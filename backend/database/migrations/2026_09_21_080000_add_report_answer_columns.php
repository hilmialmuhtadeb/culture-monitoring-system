<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'pimpinan_terlibat',
            'change_agent_berpartisipasi',
            'target_divisi_60_90_persen',
        ];

        foreach ($columns as $column) {
            if (!Schema::hasColumn('laporans', $column)) {
                Schema::table('laporans', function (Blueprint $table) use ($column) {
                    $table->boolean($column)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        $columns = array_filter([
            'pimpinan_terlibat',
            'change_agent_berpartisipasi',
            'target_divisi_60_90_persen',
        ], fn (string $column) => Schema::hasColumn('laporans', $column));

        if ($columns !== []) {
            Schema::table('laporans', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
