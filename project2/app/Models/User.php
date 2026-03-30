<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'user_id');
    }
    public function teacher()
    {
        return $this->hasMany(ClassRoom::class);
    }
    public function teacherInfo()
    {
        return $this->hasOne(Teacher::class);
    }
    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }
    public function classroom()
    {
        return $this->hasOne(Classroom::class, 'user_id');
    }
}
