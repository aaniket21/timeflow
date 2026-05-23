<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportToken extends Model
{
    use HasFactory;

    protected $table = 'report_tokens';

    protected $fillable = [
        'user_id',
        'title',
        'date_from',
        'date_to',
        'status',
        'token',
        'file_path',
        'expires_at',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
