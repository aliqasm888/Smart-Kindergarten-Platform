<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityResult extends Model
{
    protected $fillable = ['enrollment_id', 'activity_id', 'score', 'passed', 'raw_result'];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
