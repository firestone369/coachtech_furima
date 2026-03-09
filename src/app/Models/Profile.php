<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'icon_path',
        'postcode',
        'address',
        'building',
    ];

    public function getIconUrlAttribute()
    {
        return $this->icon_path
            ? asset('storage/' . $this->icon_path)
            : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
