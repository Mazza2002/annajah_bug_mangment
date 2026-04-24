<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BugStatusHistory extends Model
{
    protected $table = 'bug_status_history';

    protected $fillable = [
        'bug_id', 'user_id', 'old_status', 'new_status', 'note'
    ];

    public function bug()
    {
        return $this->belongsTo(Bug::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
