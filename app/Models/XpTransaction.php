<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XpTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'xp_transactions';

    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
