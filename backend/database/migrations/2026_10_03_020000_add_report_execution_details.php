<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->json('pimpinan_hadir')->nullable();
            $table->date('tanggal_realisasi')->nullable();
            $table->text('alasan_fokus_area')->nullable();
            $table->text('video_url')->nullable();
            $table->json('images')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropColumn([
                'pimpinan_hadir',
                'tanggal_realisasi',
                'alasan_fokus_area',
                'video_url',
                'images',
            ]);
        });
    }
};