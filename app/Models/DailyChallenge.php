<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyChallenge extends Model
{
    use HasFactory;

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $table = 'daily_challenges';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'difficulty',
        'xp_reward',
        'condition_type',
        'condition_value',
    ];

    protected $casts = [
        'xp_reward' => 'integer',
        'condition_value' => 'integer',
    ];
}
