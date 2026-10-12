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
        Schema::create('divisis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_divisi');
            $table->string('divisi_alias')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('id_divisi')
                ->references('id')
                ->on('divisis')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_divisi']);
        });

        Schema::dropIfExists('divisis');
    }
};