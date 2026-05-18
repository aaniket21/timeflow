<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimetableBlock extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'timetable_blocks';

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'color',
        'project_id',
        'days_of_week',
        'start_time',
        'end_time',
        'active',
        'semester_end',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'active' => 'boolean',
        'semester_end' => 'date',
    ];
}
