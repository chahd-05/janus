<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Habit_log extends Model
{
    protected $fillable = [
        'habit_id',
        'note',
        'date'
    ];
}
