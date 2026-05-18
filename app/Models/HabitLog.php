<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HabitLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'habit_logs';

    protected $fillable = [
        'user_id',
        'goal_id',
        'date',
        'done',
    ];

    protected $casts = [
        'date' => 'date',
        'done' => 'boolean',
    ];
}
