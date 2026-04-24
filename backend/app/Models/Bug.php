<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bug extends Model
{
    protected $fillable = [
        'project_id', 'reporter_id', 'assignee_id', 'category_id',
        'title', 'description', 'steps_to_reproduce',
        'severity', 'status', 'environment'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(BugStatusHistory::class);
    }

    public function comments()
    {
        return $this->hasMany(BugComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(BugAttachment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'bug_tags');
    }

    public function linksAsSource()
    {
        return $this->hasMany(BugLink::class, 'source_bug_id');
    }

    public function linksAsTarget()
    {
        return $this->hasMany(BugLink::class, 'target_bug_id');
    }

    public function aiSuggestions()
    {
        return $this->hasMany(AiSuggestion::class);
    }
}
