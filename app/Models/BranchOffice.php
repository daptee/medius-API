<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BranchOffice extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_user',
        'name',
        'phone',
        'address',
        'locality',
        'id_province',
    ];

    public function province(): HasOne
    {
        return $this->hasOne(Province::class, 'id', 'id_province');
    }
}
