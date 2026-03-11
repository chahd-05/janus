<?php

namespace App\Models;

use App\Models\Habits;
use Illuminate\Database\Eloquent\Model;

class HabitLog extends Model
{
    protected $fillable = [
        'note',
        'date'
    ];

    public function habit(){
        return $this->belongsTo(Habits::class);
    }
}
