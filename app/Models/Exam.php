<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'exams';

    protected $fillable = [
        'user_id',
        'subject',
        'exam_date',
        'notes',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];
}
