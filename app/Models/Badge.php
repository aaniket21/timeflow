<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $table = 'badges';

    public $timestamps = false;

    const UPDATED_AT = null;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'condition_type',
        'condition_value',
    ];

    protected $casts = [
        'condition_value' => 'integer',
    ];
}
