<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nama_divisi',
    'divisi_alias',
    'status',
])]
class Divisi extends Model
{
    use SoftDeletes;

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_divisi');
    }

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }
}