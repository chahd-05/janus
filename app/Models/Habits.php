<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Habits extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'frequency',
        'target_day',
        'is_active'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
