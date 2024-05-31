<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialtyUserStatus extends Model
{
    use HasFactory;

    protected $table = "specialties_user_status";

    const ACTIVO = 1;
    const INACTIVO = 2;

    protected $hidden = ['created_at', 'updated_at'];
}
