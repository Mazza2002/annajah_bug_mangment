<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BugAttachment extends Model
{
    protected $fillable = [
        'bug_id', 'file_path', 'file_name', 'file_size', 'mime_type'
    ];

    public function bug()
    {
        return $this->belongsTo(Bug::class);
    }
}
