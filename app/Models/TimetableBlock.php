<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableBlock extends Model
{
    use HasFactory;

    protected $table = 'timetable_blocks';

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'day_of_week',
        'start_time',
        'end_time',
        'color',
        'is_recurring',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_recurring' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
