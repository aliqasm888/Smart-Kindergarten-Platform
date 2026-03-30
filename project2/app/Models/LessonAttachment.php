<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id', 'type', 'path'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
