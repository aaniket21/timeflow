<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSession extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'time_sessions';

    protected $fillable = [
        'user_id',
        'project_id',
        'category_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'notes',
        'is_pomodoro',
        'type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
