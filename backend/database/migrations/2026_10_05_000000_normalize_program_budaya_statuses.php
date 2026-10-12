<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('program_budayas')
            ->where('status', 'On Progress')
            ->update(['status' => 'Aktif']);
    }

    public function down(): void
    {
    }
};