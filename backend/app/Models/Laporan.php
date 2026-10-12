<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'program_budaya_id',
    'user_id',
    'fokus_area_id',
    'nama',
    'alasan',
    'alasan_fokus_area',
    'pimpinan_hadir',
    'pimpinan_terlibat',
    'change_agent_berpartisipasi',
    'target_divisi_60_90_persen',
    'tanggal_realisasi',
    'video_url',
    'images',
    'image',
    'status',
    'rejection_reason',
    'approved_by',
    'approved_at',
])]
class Laporan extends Model
{
    use SoftDeletes;

    public function programBudaya(): BelongsTo
    {
        return $this->belongsTo(
            ProgramBudaya::class,
            'program_budaya_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function fokusArea(): BelongsTo
    {
        return $this->belongsTo(
            FokusArea::class,
            'fokus_area_id'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    protected function casts(): array
    {
        return [
            'pimpinan_terlibat' => 'boolean',
            'change_agent_berpartisipasi' => 'boolean',
            'target_divisi_60_90_persen' => 'boolean',
            'pimpinan_hadir' => 'array',
            'images' => 'array',
            'tanggal_realisasi' => 'date:Y-m-d',
            'approved_at' => 'datetime',
        ];
    }
}