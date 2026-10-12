<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


#[Fillable(['nama'])]
class FokusArea extends Model
{
    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class, 'fokus_area_id');
    }
}