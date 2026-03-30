<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'class_name',
        'max_students',
        'level'
    ];
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'classroom_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function schedule()
    {
        return $this->hasMany(ClassSchedule::class, 'classroom_id');
    }
    public function lessons()
    {
        return $this->belongsToMany(Lesson::class, 'class_lesson');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }




}
