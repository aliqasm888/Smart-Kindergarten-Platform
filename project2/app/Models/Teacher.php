<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'gender',
        'birth_date',
        'experience_years',
        'profile_image',
        'certificate_file',
        'work_days',
        'work_hours'
    ];

    protected $casts = [
        'work_days' => 'array',
    ];



    public function getProfileImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getCertificateFileAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
