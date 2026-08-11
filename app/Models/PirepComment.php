<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PirepComment extends Model
{
    protected $fillable = ['pirep_id', 'user_id', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
