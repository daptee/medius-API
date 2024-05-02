<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_type_user',
        'name',
        'last_name',
        'dni',
        'email',
        'password',
        'email_confirmation',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id_user_type',
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_confirmation' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function getAllDataUser($id)
    {
        return User::with(['user_type', 'company', 'branch_office', 'plan'])->find($id);
    }

    public function user_type(): HasOne
    {
        return $this->hasOne(UserType::class, 'id', 'id_user_type');
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'id_user');
    }

    public function branch_office(): HasOne
    {
        return $this->hasOne(BranchOffice::class, 'id_user');
    }

    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class, 'id', 'id_plan');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
 
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
