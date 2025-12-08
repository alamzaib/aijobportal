<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Job extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'location',
        'type',
        'salary_min',
        'salary_max',
        'salary_currency',
        'requirements',
        'benefits',
        'is_active',
        'posted_at',
    ];

    protected $casts = [
        'requirements' => 'array',
        'benefits' => 'array',
        'is_active' => 'boolean',
        'posted_at' => 'datetime',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
    ];

    /**
     * Get the company that owns the job.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the applications for the job.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get the AI job data for the job.
     */
    public function aiJob(): HasOne
    {
        return $this->hasOne(AIJob::class);
    }
}

