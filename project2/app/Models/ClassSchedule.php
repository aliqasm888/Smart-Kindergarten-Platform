<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'day',
        'period_1',
        'period_2',
        'period_3',
    ];

    public function classroom()
    {
        return $this->belongsTo(ClassRoom::class);
    }}

