<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    // EventはたくさんのUserに関わられている
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    protected $appends = ['is_registered'];

    public function getIsRegisteredAttribute()
    {
        if (!auth('sanctum')->check()) {
            return false;
        }
        return $this->users()->where('user_id', auth('sanctum')->id())->exists();
    }
}
