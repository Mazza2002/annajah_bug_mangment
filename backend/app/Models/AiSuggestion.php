<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSuggestion extends Model
{
    protected $fillable = [
        'bug_id', 'suggestion_type', 'suggestion_data', 'confidence_score', 'is_dismissed'
    ];

    protected $casts = [
        'suggestion_data' => 'json',
        'is_dismissed' => 'boolean',
        'confidence_score' => 'decimal:2',
    ];

    public function bug()
    {
        return $this->belongsTo(Bug::class);
    }
}
