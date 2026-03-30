<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_name',
        'classroom_id',
        'user_id',
        'enrol_date',
        'birth_date',
       'profile_image',
        "gender"
    ];


    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'classroom_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function activityResults()
    {
        return $this->hasMany(ActivityResult::class);
    }
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
