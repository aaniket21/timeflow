<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyChallenge extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'daily_challenges';

    protected $fillable = [
        'title',
        'description',
        'type',
        'target_value',
        'xp_reward',
    ];
}
