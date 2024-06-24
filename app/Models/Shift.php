<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id_patient',
        'id_professional',
        'date',
        'time',
        'id_branch_office',
        'overshift',
        'comments',
        'id_status'
    ];

    protected $hidden = ['id_patient', 'id_professional', 'id_branch_office', 'id_status', 'updated_at', 'deleted_at'];

    public function patient(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_patient');
    }
    
    public function professional(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id_professional');
    }

    public function branch_office(): HasOne
    {
        return $this->hasOne(BranchOffice::class, 'id', 'id_branch_office');
    }

    public function status(): HasOne
    {
        return $this->hasOne(ShiftStatus::class, 'id', 'id_status');
    }

    public static function getAllData($id)
    {
        return Shift::with(['patient', 'professional', 'branch_office', 'status'])->find($id);
    }
    
}
