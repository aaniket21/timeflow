<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserChallengeCompletion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'user_challenge_completions';

    protected $fillable = [
        'user_id',
        'challenge_id',
        'date',
        'completed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'completed_at' => 'datetime',
    ];
}
