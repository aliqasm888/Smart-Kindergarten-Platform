<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendances extends Model
{
    protected $fillable = ['enrollment_id', 'date', 'status'];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
