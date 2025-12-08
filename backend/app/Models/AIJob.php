<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'ai_generated_description',
        'ai_extracted_requirements',
        'ai_suggested_skills',
        'ai_analysis',
        'ai_model',
        'processed_at',
    ];

    protected $casts = [
        'ai_extracted_requirements' => 'array',
        'ai_suggested_skills' => 'array',
        'ai_analysis' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the job that owns the AI job data.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}

