<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('program_budayas', function (Blueprint $table) {
        $table->id();

        $table->string('nama');
        $table->text('deskripsi');
        $table->unsignedInteger('frekuensi');
        $table->year('tahun');

        $table->string('status')->default('Belum Aktif');

        $table->string('approval_document');

        $table->foreignId('created_by')
            ->constrained('users')
            ->restrictOnDelete();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_budayas');
    }
};
