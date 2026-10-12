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
    Schema::create('laporans', function (Blueprint $table) {
        $table->id();

        $table->foreignId('program_budaya_id')
            ->constrained('program_budayas')
            ->restrictOnDelete();

        $table->foreignId('user_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->foreignId('fokus_area_id')
            ->constrained('fokus_areas')
            ->restrictOnDelete();

        $table->string('nama');
        $table->text('alasan');

        $table->boolean('pimpinan_terlibat')->nullable();
        $table->boolean('change_agent_berpartisipasi')->nullable();
        $table->boolean('target_divisi_60_90_persen')->nullable();

        $table->string('image')->nullable();

        $table->string('status')->default('Draft');

        $table->text('rejection_reason')->nullable();

        $table->foreignId('approved_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->timestamp('approved_at')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporans');
    }
};
