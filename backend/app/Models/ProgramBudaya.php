<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nama',
    'deskripsi',
    'frekuensi',
    'tahun',
    'status',
    'approval_document',
    'created_by',
])]
class ProgramBudaya extends Model
{
    use SoftDeletes;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'frekuensi' => 'integer',
            'tahun' => 'integer',
        ];
    }

    public function laporans(): HasMany
    {
        return $this->hasMany(
            Laporan::class,
            'program_budaya_id'
        );
    }
}