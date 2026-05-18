<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'goals';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'target_value',
        'active',
        'reminder_time',
    ];

    protected $casts = [
        'active' => 'boolean',
        'target_value' => 'float',
    ];
}
