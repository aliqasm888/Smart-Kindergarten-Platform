<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'subject', 'description', 'date'];

    public function attachments()
    {
        return $this->hasMany(LessonAttachment::class);
    }
    public function classRooms()
    {
        return $this->belongsToMany(ClassRoom::class, 'class_lesson');
    }
}
