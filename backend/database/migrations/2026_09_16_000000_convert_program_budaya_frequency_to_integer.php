<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('program_budayas')
            ->select(['id', 'frekuensi'])
            ->orderBy('id')
            ->get()
            ->each(function (object $program): void {
                preg_match('/\d+/', (string) $program->frekuensi, $matches);

                DB::table('program_budayas')
                    ->where('id', $program->id)
                    ->update([
                        'frekuensi' => isset($matches[0])
                            ? (int) $matches[0]
                            : 1,
                    ]);
            });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE program_budayas
                ALTER COLUMN frekuensi TYPE integer
                USING frekuensi::integer
            SQL);
        } else {
            Schema::table('program_budayas', function (Blueprint $table): void {
                $table->unsignedInteger('frekuensi')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE program_budayas
                ALTER COLUMN frekuensi TYPE varchar
                USING frekuensi::text
            SQL);
        } else {
            Schema::table('program_budayas', function (Blueprint $table): void {
                $table->string('frekuensi')->change();
            });
        }
    }
};
