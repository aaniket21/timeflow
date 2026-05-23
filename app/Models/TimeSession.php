<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSession extends Model
{
    use HasFactory;

    protected $table = 'time_sessions';

    protected $fillable = [
        'user_id',
        'project_id',
        'label',
        'label_type',
        'notes',
        'started_at',
        'ended_at',
        'duration_seconds',
        'xp_earned',
        'is_pomodoro',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_pomodoro' => 'boolean',
        'duration_seconds' => 'integer',
        'xp_earned' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class, 'reference_id')
            ->where('reference_type', self::class);
    }
}
