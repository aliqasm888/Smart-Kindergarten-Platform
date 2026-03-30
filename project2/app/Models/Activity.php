<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'name',
        'type',
        'level',
        'python_script_name',
        'description'
    ];

    public function results()
    {
        return $this->hasMany(ActivityResult::class);
    }
}
