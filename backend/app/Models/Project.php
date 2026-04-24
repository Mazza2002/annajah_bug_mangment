<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name', 'description', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function bugs()
    {
        return $this->hasMany(Bug::class);
    }
}
