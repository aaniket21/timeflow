<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'badges';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'category',
        'xp_reward',
    ];
}
