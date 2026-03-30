<?php
// app/Models/Homework.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'title',
        'description',
        'due_date'
    ];

    public function classroom()
    {
        return $this->belongsTo(ClassRoom::class, 'classroom_id');
    }

}
