<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BugLink extends Model
{
    protected $fillable = [
        'source_bug_id', 'target_bug_id', 'relation_type'
    ];

    public function sourceBug()
    {
        return $this->belongsTo(Bug::class, 'source_bug_id');
    }

    public function targetBug()
    {
        return $this->belongsTo(Bug::class, 'target_bug_id');
    }
}
