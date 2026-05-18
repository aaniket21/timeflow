<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyPlan extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'daily_plans';

    protected $fillable = [
        'user_id',
        'date',
        'tasks',
    ];

    protected $casts = [
        'date' => 'date',
        'tasks' => 'array',
    ];
}
