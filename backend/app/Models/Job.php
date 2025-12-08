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

    /**
     * Generate a slug from the job title.
     */
    public function getSlugAttribute(): string
    {
        $titleSlug = strtolower($this->title);
        $titleSlug = preg_replace('/[^a-z0-9]+/', '-', $titleSlug);
        $titleSlug = trim($titleSlug, '-');
        return $titleSlug . '--' . $this->id;
    }

    /**
     * Find a job by slug or UUID.
     * Supports formats: "uuid" or "title-slug--uuid" or "title-slug"
     */
    public static function findBySlugOrId(string $identifier)
    {
        // Try UUID first (if it's a valid UUID format)
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier)) {
            return static::find($identifier);
        }

        // Try to extract UUID from slug format: "title-slug--uuid"
        if (strpos($identifier, '--') !== false) {
            $parts = explode('--', $identifier);
            $uuid = end($parts);
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
                return static::find($uuid);
            }
        }

        // Try to find by title slug
        // Use database query for better performance
        try {
            $searchSlug = strtolower($identifier);
            $searchSlug = preg_replace('/[^a-z0-9]+/', '-', $searchSlug);
            $searchSlug = trim($searchSlug, '-');
            
            // Convert slug back to searchable title format
            // "database-administrator" -> "Database Administrator"
            $searchTitle = str_replace('-', ' ', $searchSlug);
            $searchTitle = ucwords($searchTitle);
            
            // Try to find by matching the slug pattern in the title
            // This is more efficient than loading all jobs
            $jobs = static::where('is_active', true)
                ->where(function($query) use ($searchSlug, $searchTitle) {
                    // Match if title contains all words from slug
                    $words = explode('-', $searchSlug);
                    foreach ($words as $word) {
                        if (strlen($word) > 0) {
                            $query->whereRaw('LOWER(title) LIKE ?', ['%' . $word . '%']);
                        }
                    }
                })
                ->get();
            
            // Now match by slug in PHP for exact/partial matching
            foreach ($jobs as $job) {
                // Generate slug from job title
                $jobSlug = strtolower($job->title);
                $jobSlug = preg_replace('/[^a-z0-9]+/', '-', $jobSlug);
                $jobSlug = trim($jobSlug, '-');
                
                // Exact match
                if ($jobSlug === $searchSlug) {
                    return $job;
                }
                
                // Check if search slug is contained in job slug
                if (strpos($jobSlug, $searchSlug) !== false) {
                    return $job;
                }
                
                // Also try reverse - if job slug is contained in search slug
                if (strpos($searchSlug, $jobSlug) !== false) {
                    return $job;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error in findBySlugOrId: ' . $e->getMessage(), [
                'identifier' => $identifier,
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - return null so controller can handle 404 gracefully
            return null;
        }
        
        return null;
    }
}

