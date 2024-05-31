<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchOffice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id_user',
        'name',
        'phone',
        'email',
        'address',
        'postal_code',
        'locality',
        'id_province',
        'id_status'
    ];

    protected $hidden = ['id_user', 'id_province', 'id_status', 'created_at', 'updated_at', 'deleted_at'];

    public function province(): HasOne
    {
        return $this->hasOne(Province::class, 'id', 'id_province');
    }
}
